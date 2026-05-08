<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class DiagnosticController extends Controller
{
    /**
     * View recent logs from browser (for production debugging without terminal access)
     * SECURITY: Only accessible by authenticated users with admin/manager role
     */
    public function viewLogs(Request $request)
    {
        // Check authentication
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }
        
        // Check if user is admin or manager
        $user = Auth::user();
        $roleKey = session('auth_role_key');
        
        if (!in_array($roleKey, ['admin', 'manager'])) {
            abort(403, 'Only admin and manager can view logs');
        }
        
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found',
                'log_file' => $logFile
            ]);
        }
        
        // Get number of lines to read (default 200, max 1000)
        $lines = min((int) $request->get('lines', 200), 1000);
        
        // Get filter keyword
        $filter = $request->get('filter', '');
        
        try {
            // Read last N lines from log file
            $command = "tail -n {$lines} " . escapeshellarg($logFile);
            $logContent = shell_exec($command);
            
            // If shell_exec not available, use PHP to read file
            if ($logContent === null) {
                $file = new \SplFileObject($logFile, 'r');
                $file->seek(PHP_INT_MAX);
                $totalLines = $file->key() + 1;
                
                $startLine = max(0, $totalLines - $lines);
                $logLines = [];
                
                $file->seek($startLine);
                while (!$file->eof()) {
                    $logLines[] = $file->current();
                    $file->next();
                }
                
                $logContent = implode('', $logLines);
            }
            
            // Filter logs if keyword provided
            if (!empty($filter)) {
                $allLines = explode("\n", $logContent);
                $filteredLines = array_filter($allLines, function($line) use ($filter) {
                    return stripos($line, $filter) !== false;
                });
                $logContent = implode("\n", $filteredLines);
            }
            
            // Return as HTML view or JSON based on request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'lines_read' => $lines,
                    'filter' => $filter,
                    'log_file' => $logFile,
                    'log_content' => $logContent
                ]);
            }
            
            // Return HTML view
            return view('staff.diagnostic.logs', [
                'logContent' => $logContent,
                'lines' => $lines,
                'filter' => $filter,
                'logFile' => $logFile
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reading log file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * View notification logs (filtered for notification-related logs)
     */
    public function viewNotificationLogs(Request $request)
    {
        // Check authentication
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }
        
        // Check if user is admin or manager
        $user = Auth::user();
        $roleKey = session('auth_role_key');
        
        if (!in_array($roleKey, ['admin', 'manager'])) {
            abort(403, 'Only admin and manager can view logs');
        }
        
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found',
                'log_file' => $logFile
            ]);
        }
        
        // Get number of lines to read (default 200, max 1000)
        $lines = min((int) $request->get('lines', 200), 1000);
        
        // Get filter keyword (default to NotificationFromAuditService if not provided)
        $filter = $request->get('filter', 'NotificationFromAuditService');
        
        try {
            // Read last N lines from log file
            $command = "tail -n {$lines} " . escapeshellarg($logFile);
            $logContent = shell_exec($command);
            
            // If shell_exec not available, use PHP to read file
            if ($logContent === null) {
                $file = new \SplFileObject($logFile, 'r');
                $file->seek(PHP_INT_MAX);
                $totalLines = $file->key() + 1;
                
                $startLine = max(0, $totalLines - $lines);
                $logLines = [];
                
                $file->seek($startLine);
                while (!$file->eof()) {
                    $logLines[] = $file->current();
                    $file->next();
                }
                
                $logContent = implode('', $logLines);
            }
            
            // Filter logs if keyword provided
            if (!empty($filter)) {
                $allLines = explode("\n", $logContent);
                $filteredLines = array_filter($allLines, function($line) use ($filter) {
                    return stripos($line, $filter) !== false;
                });
                $logContent = implode("\n", $filteredLines);
            }
            
            // Return as HTML view or JSON based on request
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'lines_read' => $lines,
                    'filter' => $filter,
                    'log_file' => $logFile,
                    'log_content' => $logContent
                ]);
            }
            
            // Return HTML view
            return view('staff.diagnostic.notification-logs', [
                'logContent' => $logContent,
                'lines' => $lines,
                'filter' => $filter,
                'logFile' => $logFile
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reading log file: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get last error from logs related to payslips
     */
    public function getPayslipErrors(Request $request)
    {
        // Check authentication
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }
        
        $roleKey = session('auth_role_key');
        if (!in_array($roleKey, ['admin', 'manager'])) {
            abort(403, 'Only admin and manager can view logs');
        }
        
        $logFile = storage_path('logs/laravel.log');
        
        if (!File::exists($logFile)) {
            return response()->json([
                'success' => false,
                'message' => 'Log file not found'
            ]);
        }
        
        try {
            // Read last 500 lines
            $command = "tail -n 500 " . escapeshellarg($logFile);
            $logContent = shell_exec($command);
            
            if ($logContent === null) {
                // Fallback to PHP file reading
                $file = new \SplFileObject($logFile, 'r');
                $file->seek(PHP_INT_MAX);
                $totalLines = $file->key() + 1;
                
                $startLine = max(0, $totalLines - 500);
                $logLines = [];
                
                $file->seek($startLine);
                while (!$file->eof()) {
                    $logLines[] = $file->current();
                    $file->next();
                }
                
                $logContent = implode('', $logLines);
            }
            
            // Filter for payslip-related errors
            $keywords = [
                'generatePayslips',
                'createFromPreview',
                'Error generating payslips',
                'Error creating payslip',
                'calculateMonthlyDeduction',
                'Error calculating commission',
                'No organization ID',
                'SalaryAdvance'
            ];
            
            $allLines = explode("\n", $logContent);
            $relevantLines = [];
            
            foreach ($allLines as $line) {
                foreach ($keywords as $keyword) {
                    if (stripos($line, $keyword) !== false) {
                        $relevantLines[] = $line;
                        break;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'error_count' => count($relevantLines),
                'errors' => array_slice($relevantLines, -50), // Last 50 relevant logs
                'keywords_searched' => $keywords
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error reading log file: ' . $e->getMessage()
            ], 500);
        }
    }
}

