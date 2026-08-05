<?php
$file = 'app/Http/Controllers/AdmissionAdminController.php';
$content = file_get_contents($file);

$method = "
    public function getN8nLogs()
    {
        \$logs = \Illuminate\Support\Facades\DB::table('admission_n8n_logs')
            ->orderBy('id', 'desc')
            ->limit(50)
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => \$logs
        ]);
    }
}
";

$content = preg_replace('/\}\s*$/', $method, $content);
file_put_contents($file, $content);
echo "Method added successfully.";
