<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
class DatabaseSeeder extends Seeder { public function run(): void {
 DB::table('users')->insert([
 ['id'=>1,'name'=>'Budi Santoso','email'=>'siswa@smartschool.test','password'=>Hash::make('siswa123'),'role'=>'siswa','phone'=>'081234567801','address'=>'Kota Kupang, Nusa Tenggara Timur','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
 ['id'=>2,'name'=>'Ibu Maria Lestari','email'=>'guru@smartschool.test','password'=>Hash::make('guru123'),'role'=>'guru','phone'=>'081234567802','address'=>'Kota Kupang, Nusa Tenggara Timur','is_active'=>1,'created_at'=>now(),'updated_at'=>now()],
 ['id'=>3,'name'=>'Bapak Anton Santoso','email'=>'ortu@smartschool.test','password'=>Hash::make('ortu123'),'role'=>'ortu','phone'=>'081234567803','address'=>'Kota Kupang, Nusa Tenggara Timur','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]]);
 DB::table('students')->insert(['id'=>1,'user_id'=>1,'student_code'=>'SSK001','nisn'=>'0098765432','class_name'=>'XII TKJ 1','major'=>'Teknik Komputer dan Jaringan','school_year'=>'2026/2027','created_at'=>now()]); DB::table('teachers')->insert(['id'=>1,'user_id'=>2,'teacher_code'=>'GRU001','nip'=>'198706152012012001','main_subject'=>'Matematika Dasar','created_at'=>now()]); DB::table('parents')->insert(['id'=>1,'user_id'=>3,'parent_code'=>'ORT001','relationship'=>'ayah','created_at'=>now()]); DB::table('parent_students')->insert(['parent_id'=>1,'student_id'=>1,'created_at'=>now()]);
 foreach([['MTK','Matematika Dasar','Matematika dasar dan aljabar.'],['BIN','Bahasa Indonesia','Bahasa, sastra, dan komunikasi.'],['FIS','Fisika Terapan','Fisika untuk kebutuhan industri.'],['KIM','Kimia Industri','Kimia dasar dan proses industri.']] as [$code,$name,$desc]) DB::table('subjects')->insert(['subject_code'=>$code,'name'=>$name,'description'=>$desc,'teacher_id'=>1,'created_at'=>now(),'updated_at'=>now()]);
 foreach([[1,'Modul 01 - Aljabar Dasar','Persamaan, variabel, dan operasi dasar aljabar.','Matematika Dasar'],[3,'Hukum Newton','Ringkasan hukum gerak Newton dan latihan.','Fisika Terapan'],[2,'Analisis Puisi','Panduan mengenali unsur intrinsik puisi.','Bahasa Indonesia'],[4,'Pengantar Kimia Industri','Zat, unsur, dan proses industri dasar.','Kimia Industri'],[1,'Statistika Dasar','Mean, median, modus, dan penyajian data.','Matematika Dasar']] as $i=>[$sid,$title,$desc,$subject]) { $stored='materials/seed-materi-'.($i+1).'.txt'; $content="SMART SCHOOL KUPANG\n\n{$title}\nMata Pelajaran: {$subject}\n\n{$desc}\n"; Storage::disk('public')->put($stored,$content); DB::table('materials')->insert(['subject_id'=>$sid,'teacher_id'=>1,'title'=>$title,'description'=>$desc,'file_name'=>$stored,'original_name'=>'materi-'.($i+1).'.txt','file_type'=>'txt','file_size'=>strlen($content),'is_published'=>1,'created_at'=>now(),'updated_at'=>now()]); }
 DB::table('quizzes')->insert(['id'=>1,'subject_id'=>1,'teacher_id'=>1,'title'=>'Kuis Matematika Dasar','description'=>'Evaluasi operasi hitung dan aljabar dasar.','duration_minutes'=>30,'passing_score'=>75,'is_published'=>1,'created_at'=>now(),'updated_at'=>now()]); foreach([['Hasil dari 8 x 7 adalah...','48','56','64','72','b'],['Nilai x dari 2x + 4 = 14 adalah...','3','4','5','6','c'],['Hasil 12 kuadrat adalah...','124','144','164','184','b']] as $i=>$q) DB::table('quiz_questions')->insert(['quiz_id'=>1,'question_text'=>$q[0],'option_a'=>$q[1],'option_b'=>$q[2],'option_c'=>$q[3],'option_d'=>$q[4],'correct_answer'=>$q[5],'points'=>1,'question_order'=>$i+1,'created_at'=>now()]);
 DB::table('quiz_attempts')->insert(['id'=>1,'quiz_id'=>1,'student_id'=>1,'score'=>66.67,'correct_answers'=>2,'total_questions'=>3,'submitted_at'=>now(),'status'=>'selesai']); foreach([[1,'b',1],[2,'c',1],[3,'a',0]] as [$qid,$selected,$ok]) DB::table('quiz_answers')->insert(['attempt_id'=>1,'question_id'=>$qid,'selected_answer'=>$selected,'is_correct'=>$ok,'points_earned'=>$ok]);
 foreach([['FSL001','Laptop Praktik','Laptop untuk praktikum.',24,24],['FSL002','Kamera DSLR','Kamera dokumentasi dan multimedia.',8,7],['FSL003','Proyektor Kelas','Proyektor untuk presentasi.',12,12],['FSL004','Ruang Multimedia','Studio multimedia sekolah.',1,1]] as [$code,$name,$desc,$total,$avail]) DB::table('facilities')->insert(['facility_code'=>$code,'name'=>$name,'description'=>$desc,'total_stock'=>$total,'available_stock'=>$avail,'is_active'=>1,'created_at'=>now()]); DB::table('facility_requests')->insert(['student_id'=>1,'facility_id'=>2,'purpose'=>'Dokumentasi tugas multimedia','date_needed'=>now()->addDays(3)->toDateString(),'status'=>'menunggu','created_at'=>now(),'updated_at'=>now()]);
 foreach([[1,'Materi Baru','Modul Aljabar Dasar telah diterbitkan.','/academic'],[2,'Pengajuan Fasilitas','Budi Santoso mengajukan peminjaman Kamera DSLR.','/facilities'],[3,'Nilai Kuis Anak','Nilai Kuis Matematika Dasar Budi telah tersedia.','/grades']] as [$uid,$title,$message,$link]) DB::table('notifications')->insert(['user_id'=>$uid,'title'=>$title,'message'=>$message,'link'=>$link,'is_read'=>0,'created_at'=>now()]);

 // Akun dummy (Faker) untuk pengujian authentication
 $this->call(UserSeeder::class);

 $this->seedFacilityRequests();
 }

 /** Menambah pengajuan fasilitas contoh sehingga modul Facility memiliki >= 5 data (beberapa dengan lampiran). */
 private function seedFacilityRequests(): void
 {
  $students = DB::table('students')->orderBy('id')->pluck('id')->all();
  $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
  $rows = [
   [$students[1] ?? 1, 1, 'Praktikum jaringan komputer kelas XI', 2, 'disetujui', 'surat-praktikum.png'],
   [$students[2] ?? 1, 3, 'Presentasi proyek akhir pemrograman web', 4, 'dipinjam', null],
   [$students[3] ?? 1, 4, 'Pengambilan video profil jurusan', 5, 'menunggu', 'surat-multimedia.png'],
   [$students[0] ?? 1, 1, 'Latihan ujian kompetensi keahlian', 6, 'ditolak', null],
  ];
  foreach ($rows as $i => [$sid, $fid, $purpose, $days, $status, $file]) {
   $path = null; $name = null;
   if ($file) { $path = 'facility-attachments/seed-'.($i+1).'.png'; $name = $file; Storage::disk('public')->put($path, $png); }
   DB::table('facility_requests')->insert(['student_id'=>$sid,'facility_id'=>$fid,'purpose'=>$purpose,'date_needed'=>now()->addDays($days)->toDateString(),'return_date'=>now()->addDays($days+1)->toDateString(),'status'=>$status,'teacher_note'=>$status==='ditolak'?'Jadwal bentrok dengan kegiatan sekolah.':null,'attachment_path'=>$path,'attachment_name'=>$name,'created_at'=>now(),'updated_at'=>now()]);
  }
 }
}
