<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Response;

class DatabaseController extends Controller
{
    protected static $DB_PATH;
    protected static $BACKUP_PATH;
    protected static $BACKUP_PREFIX;
    protected static $DB_NAME;
    public function __construct()
    {
        self::$DB_PATH     = database_path();
        self::$BACKUP_PATH = database_path('backup');
        self::$BACKUP_PREFIX = Cache::get('institute')["short_name"];
        self::$DB_NAME     = env("DB_DATABASE");
    }

    public function index(){
      $files = $this->backups();
      $url = database_path()."/database.sqlite";
      return inertia("Database", compact("url", "files"));
    }
    
    public function download(){
      $file = self::$DB_PATH. DIRECTORY_SEPARATOR . self::$DB_NAME;
      return Response()->download($file);
    }
    
    public function upload(Request $request)
    {
        $extensions = ["sqlite", "db"];
        
        $request->validate([
            'file' => 'required|file|max:51200',
        ]);
        
        if (!in_array($request->file('file')->getClientOriginalExtension(), $extensions)) {
            return back()->withErrors(['file' => 'Invalid SQLite file']);
        }
        
        return redirect()
          ->back()
          ->with("toast", $this->replaceDatabase($request->file('file'), $request->input("file_name")));
    }
    
    public function restore($fileName)
    {
        $backupFile = self::$BACKUP_PATH . DIRECTORY_SEPARATOR . basename($fileName);
        return redirect()
          ->back()
          ->with("toast", $this->replaceDatabase($backupFile));
    }
    
    public function deleteBackup($file)
    {
        // Prevent directory traversal attacks
        $file = basename($file);
        
        $path = self::$BACKUP_PATH . DIRECTORY_SEPARATOR . $file;
    
        if (!File::exists($path)) {
            return back()->withErrors(['msg' => 'Backup file not found.']);
        }
    
        File::delete($path);
        $toast = [
          "message" => "Backup file deleted successful.",
          "type" => "success",
        ];
        return redirect()
          ->back()
          ->with("toast", $toast);
    
    }
    
    protected function replaceDatabase($sourcePath, $backup_name = "backup")
    {
        File::ensureDirectoryExists(self::$DB_PATH);
        File::ensureDirectoryExists(self::$BACKUP_PATH);
        
        // Backup current DB if exists
        if (File::exists(self::$DB_PATH)) {
            $backupName = self::$BACKUP_PREFIX. "_".$backup_name. '_' . time() . '.sqlite';
            File::move(self::$DB_PATH. DIRECTORY_SEPARATOR . self::$DB_NAME, self::$BACKUP_PATH . DIRECTORY_SEPARATOR . $backupName);
        }

        // Move new DB file to active location
        File::move($sourcePath, self::$DB_PATH. DIRECTORY_SEPARATOR . self::$DB_NAME);

        // Reconnect SQLite
        //DB::purge('sqlite');
        //DB::reconnect('sqlite');
        Cache::forget('institute');
        return [
          "message" => 'Database replaced successfully.',
          "type" => "success"
        ];
    }
    
    private function backups()
    {
        $backupPath = database_path('backup');
    
        if (!File::exists($backupPath)) {
            $files = [];
        } else {
            $files = collect(File::files($backupPath))
                ->map(function ($file) {
                    preg_match('/backup_(\d+)\.sqlite/', $file->getFilename(), $matches);

                    $timestamp = $matches[1] ?? $file->getCTime(); // fallback
                    return [
                        'name' => $file->getFilename(),
                        'size' => round($file->getSize() / 1024, 2), // KB
                        'date' => date('d-m-Y', $timestamp),
                        'time' => date('h:i:s A', $timestamp),
                    ];
                })
                ->sortByDesc('time')
                ->values();
        }
        return $files;
    }
    
    
}





