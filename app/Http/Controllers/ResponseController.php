<?
namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\Response; // Pastikan ini adalah nama model Response Anda
use App\Models\ResponseAnswer;
use App\Models\Student;
use App\Models\Question; 
use App\Models\Teacher; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\FormResponseResource;
use App\Http\Resources\AnswerResource;


class ResponseController extends Controller
{
    public function index()
    {
        $responses = Response::with('answers')->get();
        return view('responses.index', compact('responses'));
    }

    public function destroy(Response $response)
    {
        $response->delete();
        return redirect()->route('responses.index')->with('success', 'Response deleted.');
    }

    // API
    public function apiIndex()
    {
        return response()->json(Response::with('answers')->get());
    }

    public function apiStore(Request $request)
    {
        $user = $request->user();

        // 1. Validasi Autentikasi: Pastikan user yang login adalah siswa
        if (!$user || !($user instanceof Student)) {
            return response()->json(['message' => 'Akses ditolak: Hanya siswa yang dapat mengirimkan respons.'], 403);
        }

        // 2. Validasi Input: Memeriksa semua data yang dikirim dari Android
        $validator = Validator::make($request->all(), [
            'form_id' => 'required|integer|exists:forms,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'answers' => 'required|json', // Android mengirim string JSON
            'photo' => 'required|image|mimes:jpeg,png,jpg|max:4096', // Foto wajib, maks 4MB
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Data yang dikirim tidak valid.', 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        $form = Form::findOrFail($validatedData['form_id']);

        // 3. Proses dan Simpan File Foto
        $photoPath = $request->file('photo')->store('response_photos', 'public');

        // 4. Buat record Response di database dengan semua data yang relevan
        $formResponse = Response::create([
            'form_id' => $form->id,
            'student_id' => $user->id,
            'photo_path' => $photoPath, // Simpan path relatif ke foto
            'latitude' => $validatedData['latitude'] ?? null,
            'longitude' => $validatedData['longitude'] ?? null,
            // Asumsi validasi lokasi dilakukan di client, atau bisa ditambahkan logika server di sini
            'is_location_valid' => $request->input('is_location_valid_from_client', true),
            'submitted_at' => now(),
        ]);

        // 5. Proses dan simpan setiap jawaban dari string JSON
        $answersArray = json_decode($validatedData['answers'], true);
        if (is_array($answersArray)) {
            foreach ($answersArray as $answerData) {
                if (isset($answerData['question_id']) && array_key_exists('answer_text', $answerData)) {
                    $questionExists = Question::where('id', $answerData['question_id'])
                                              ->where('form_id', $form->id)
                                              ->exists();
                    if ($questionExists) {
                        ResponseAnswer::create([
                            'response_id' => $formResponse->id,
                            'question_id' => $answerData['question_id'],
                            'answer_text' => $answerData['answer_text'] ?? null,
                        ]);
                    }
                }
            }
        }

        // 6. Kembalikan data yang baru dibuat menggunakan API Resource
        $formResponse->load(['student', 'form.teacher', 'answers.question']);
        return new FormResponseResource($formResponse);
    }
  
  
  public function apiIndexByForm(Request $request, Form $form)
	{
    // Logika untuk otorisasi dan mengambil data...
    // Contoh:
    if ($request->user()->id !== $form->teacher_id) {
        return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $responses = Response::where('form_id', $form->id)
                                     ->with('student')
                                     ->latest('submitted_at')
                                     ->get();

    return \App\Http\Resources\FormResponseResource::collection($responses);
	}
  
  
  
  public function indexByForm(Request $request, Form $form)
    {
        $user = $request->user();

        // Autorisasi: Pastikan guru yang meminta adalah pemilik formulir
        if (!$user instanceof Teacher || $user->id !== $form->teacher_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Ambil semua response untuk form ini, beserta relasi yang diperlukan
        // PERBAIKAN ADA DI SINI: Ubah 'submitted_at' menjadi 'created_at'
        $responses = $form->responses()
                          ->with(['student', 'answers.question'])
                          ->orderBy('created_at', 'desc') // <-- UBAH DI SINI
                          ->get();

        if ($responses->isEmpty()) {
            return response()->json(['message' => 'Belum ada siswa yang mengisi formulir ini.'], 200);
        }
        
        return FormResponseResource::collection($responses);
    }
  

    /**
     * Menampilkan detail spesifik dari sebuah respons, termasuk foto dan lokasi.
     * Ini adalah endpoint untuk fitur "lihat detail riwayat".
     */
    public function apiShowResponseDetail(Request $request, Response $response)
    {
        $user = $request->user();

        // Logika otorisasi (opsional tapi sangat disarankan)
        $isOwner = ($user instanceof Student && $user->id === $response->student_id);
        $isTeacherOfForm = ($user instanceof Teacher && $user->id === $response->form->teacher_id);

        if (!$isOwner && !$isTeacherOfForm) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Eager load semua relasi yang dibutuhkan oleh resource
        $response->load(['student', 'form.teacher', 'answers.question.options']);

        // Kembalikan data menggunakan resource untuk konsistensi format
        return new FormResponseResource($response);
    }

    public function apiDestroy(Response $response)
    {
        $response->delete();
        return response()->json(['message' => 'Response deleted']);
    }
}
