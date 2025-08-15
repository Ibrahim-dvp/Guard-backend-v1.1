<?php
/**
 * GitHub Webhook Handler for Guard Backend Auto-Deployment
 * 
 * This script handles GitHub webhooks to automatically deploy
 * the Guard Backend API when changes are pushed to the main branch.
 * 
 * Security: Uses HMAC SHA256 to verify webhook authenticity
 * Logging: All deployment activities are logged for monitoring
 */

// Configuration
$hookSecret = 'OszfR5NYNHjF6jetV0HCKN2UbJIQ5idXD6sJTnCk65b754ea'; // Change this to a secure secret
$projectDir = '/var/www/api.zmachine.pro';
$logFile = '/var/log/guard-auto-deploy.log';
$allowedBranches = ['refs/heads/main', 'refs/heads/master'];

// Security: Verify webhook signature
function verifySignature($payload, $signature, $secret) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Logging function
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp] $message\n", 3, $logFile);
}

// Get request data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// Verify webhook signature
if (!verifySignature($payload, $signature, $hookSecret)) {
    http_response_code(401);
    logMessage('ERROR: Unauthorized webhook request - Invalid signature');
    exit(json_encode(['error' => 'Unauthorized']));
}

// Parse payload
$data = json_decode($payload, true);

if (!$data) {
    http_response_code(400);
    logMessage('ERROR: Invalid JSON payload');
    exit(json_encode(['error' => 'Invalid payload']));
}

// Check if this is a push event
if (!isset($data['ref'])) {
    logMessage('INFO: Webhook received but not a push event - ignoring');
    exit(json_encode(['status' => 'ignored', 'message' => 'Not a push event']));
}

// Check if push is to allowed branch
if (!in_array($data['ref'], $allowedBranches)) {
    logMessage("INFO: Push to {$data['ref']} - ignoring (not main/master branch)");
    exit(json_encode(['status' => 'ignored', 'message' => 'Not target branch']));
}

// Extract commit information
$pusher = $data['pusher']['name'] ?? 'unknown';
$commits = $data['commits'] ?? [];
$commitCount = count($commits);
$headCommit = $data['head_commit'] ?? null;
$commitMessage = $headCommit['message'] ?? 'No commit message';
$commitId = substr($headCommit['id'] ?? 'unknown', 0, 7);

logMessage("INFO: Auto-deployment triggered by $pusher");
logMessage("INFO: Branch: {$data['ref']}");
logMessage("INFO: Commits: $commitCount");
logMessage("INFO: Latest commit: [$commitId] $commitMessage");

// Change to project directory
if (!chdir($projectDir)) {
    http_response_code(500);
    logMessage("ERROR: Could not change to project directory: $projectDir");
    exit(json_encode(['error' => 'Project directory not accessible']));
}

// Execute deployment script
$deployCommand = "$projectDir/deploy.sh production 2>&1";
logMessage("INFO: Executing deployment command: $deployCommand");

$startTime = microtime(true);
$output = shell_exec($deployCommand);
$endTime = microtime(true);
$duration = round($endTime - $startTime, 2);

// Check if deployment was successful
$exitCode = 0;
exec($deployCommand, $outputArray, $exitCode);

if ($exitCode === 0) {
    logMessage("SUCCESS: Deployment completed successfully in {$duration}s");
    logMessage("INFO: Deployment output: " . trim($output));
    
    // Optional: Send success notification
    // sendSlackNotification("✅ Guard Backend deployed successfully! Commit: [$commitId] $commitMessage");
    
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment completed successfully',
        'duration' => $duration,
        'commit' => $commitId,
        'pusher' => $pusher
    ]);
} else {
    logMessage("ERROR: Deployment failed with exit code $exitCode");
    logMessage("ERROR: Deployment output: " . trim($output));
    
    // Optional: Send failure notification
    // sendSlackNotification("❌ Guard Backend deployment failed! Commit: [$commitId] $commitMessage");
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Deployment failed',
        'exit_code' => $exitCode,
        'output' => $output
    ]);
}

// Optional: Slack notification function
function sendSlackNotification($message) {
    $webhookUrl = ''; // Add your Slack webhook URL here
    
    if (empty($webhookUrl)) {
        return;
    }
    
    $payload = json_encode(['text' => $message]);
    
    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
?>
