<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
// use Illuminate\Support\Facades\View;        // tambahkan ini
// use Illuminate\Support\Facades\Redirect;    // tambahkan ini
// use Illuminate\Support\Facades\Response;    // tambahkan ini
use App\Http\Resources\FormResponseResource;

class StudentController extends Controller
{
    public function index(Request $request) {
        $query = Student::query();
    
        // Filter berdasarkan gender
        if ($request->has('gender') && $request->gender !== '') {
            $query->where('gender', $request->gender);
        }
    
        // Filter berdasarkan kelas
        if ($request->has('grade') && $request->grade !== '') {
            $query->where('grade', $request->grade);
        }
    
        $students = $query->get();
    
        return view('students.index', compact('students'));
    }
    

    public function create() {
        return view('students.create');
    }

    public function store(Request $request) {
        $request->validate([
            'name' => 'required',
            'gender' => 'required|boolean',
            'email' => 'required|email|unique:students',
            'password' => 'required|min:6',
            'grade' => 'required',
            'address' => 'nullable|string',
        ]);
    
        Student::create([
            'name' => $request->name,
            'gender' => $request->gender,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'grade' => $request->grade,
            'address' => $request->address,
        ]);
    
        return redirect()->route('students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }
    
    //menampilkan data api json
    public function apiIndex()
    {
        $query = Student::all();
        return response()->json([
            'status' => true,
            'message' => 'Data murid ditemukan',
            'data' => $query
        ], 200);
    }
  
  	public function favoriteForms()
    {
        // 'user_type' akan dicocokkan dengan nama class ini
        return $this->morphToMany(Form::class, 'user', 'favorite_forms');
    }
  
    public function apiGetResponseHistory(Request $request)
    {
        $user = $request->user(); // Mendapatkan siswa yang terautentikasi

        if (!$user || !$user instanceof Student) {
            return response()->json(['message' => 'Unauthorized or not a student.'], 403);
        }

        // Ambil semua 'responses' milik siswa, beserta relasi 'form'
        // 'form.teacher' juga di-load agar nama guru bisa ditampilkan jika perlu
        $responses = $user->submittedResponses()
                           ->with(['form.teacher'])
                           ->latest() // Urutkan berdasarkan yang terbaru diisi
                           ->get();

        if ($responses->isEmpty()) {
            return response()->json(['message' => 'Anda belum mengisi formulir apapun.', 'data' => []], 200);
        }

        // Gunakan FormResponseResource untuk format data yang konsisten
        return FormResponseResource::collection($responses);
    }
  
}
