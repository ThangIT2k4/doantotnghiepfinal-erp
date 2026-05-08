<?php
/**
 * Test file để debug Sepay payment method display
 * Usage: Truy cập http://your-domain.com/test.php?org_id=1&invoice_id=1
 */

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/html; charset=utf-8');

// Get parameters
$orgId = $_GET['org_id'] ?? null;
$invoiceId = $_GET['invoice_id'] ?? null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sepay Payment Debug Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { margin: 20px 0; padding: 15px; background: #252526; border-left: 4px solid #007acc; }
        .section h2 { margin-top: 0; color: #4ec9b0; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #ce9178; }
        .info { color: #569cd6; }
        pre { background: #1e1e1e; padding: 10px; border: 1px solid #3e3e42; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        table td, table th { padding: 8px; text-align: left; border: 1px solid #3e3e42; }
        table th { background: #2d2d30; color: #cccccc; }
        .badge { padding: 2px 8px; border-radius: 3px; font-size: 12px; }
        .badge-success { background: #107c10; }
        .badge-error { background: #e81123; }
        .badge-warning { background: #ca5010; }
        input { padding: 8px; background: #3c3c3c; border: 1px solid #6c6c6c; color: #fff; }
        button { padding: 8px 16px; background: #0e639c; border: none; color: #fff; cursor: pointer; }
        button:hover { background: #1177bb; }
    </style>
</head>
<body>

<h1>🔍 Sepay Payment Method Debug Test</h1>

<form method="get" style="margin-bottom: 20px;">
    <label>Organization ID: <input type="number" name="org_id" value="<?= htmlspecialchars($orgId ?? '') ?>" placeholder="1"></label>
    <label>Invoice ID: <input type="number" name="invoice_id" value="<?= htmlspecialchars($invoiceId ?? '') ?>" placeholder="1"></label>
    <button type="submit">Test</button>
</form>

<?php if ($orgId || $invoiceId): ?>

<div class="section">
    <h2>📋 Test Parameters</h2>
    <table>
        <tr><th>Parameter</th><th>Value</th></tr>
        <tr><td>Organization ID</td><td><?= $orgId ?? '<span class="warning">Not provided</span>' ?></td></tr>
        <tr><td>Invoice ID</td><td><?= $invoiceId ?? '<span class="warning">Not provided</span>' ?></td></tr>
    </table>
</div>

<?php
// Test 1: Check if organization exists
echo '<div class="section">';
echo '<h2>1️⃣ Organization Check</h2>';

if ($orgId) {
    $org = \App\Models\Organization::find($orgId);
    if ($org) {
        echo '<p class="success">✅ Organization found: ' . htmlspecialchars($org->name) . '</p>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        echo '<tr><td>ID</td><td>' . $org->id . '</td></tr>';
        echo '<tr><td>Name</td><td>' . htmlspecialchars($org->name) . '</td></tr>';
        echo '<tr><td>Code</td><td>' . htmlspecialchars($org->organization_code ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Status</td><td>' . ($org->status ?? 'N/A') . '</td></tr>';
        echo '</table>';
    } else {
        echo '<p class="error">❌ Organization not found with ID: ' . $orgId . '</p>';
        $org = null;
    }
} else {
    echo '<p class="warning">⚠️ No organization ID provided</p>';
    $org = null;
}
echo '</div>';

// Test 2: Check subscription
echo '<div class="section">';
echo '<h2>2️⃣ Subscription Check</h2>';

if ($org) {
    $subscription = $org->activeSubscription;
    
    if ($subscription) {
        $isValid = $subscription->isValid();
        echo '<p class="success">✅ Active subscription found</p>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        echo '<tr><td>ID</td><td>' . $subscription->id . '</td></tr>';
        echo '<tr><td>Status</td><td><span class="badge badge-' . ($subscription->status == 'active' || $subscription->status == 'trial' ? 'success' : 'error') . '">' . strtoupper($subscription->status) . '</span></td></tr>';
        echo '<tr><td>Start Date</td><td>' . ($subscription->current_period_start ? $subscription->current_period_start->format('Y-m-d H:i:s') : 'N/A') . '</td></tr>';
        echo '<tr><td>End Date</td><td>' . ($subscription->current_period_end ? $subscription->current_period_end->format('Y-m-d H:i:s') : '<span class="info">UNLIMITED</span>') . '</td></tr>';
        
        if ($subscription->current_period_end) {
            $isExpired = $subscription->current_period_end->isPast();
            echo '<tr><td>Expired?</td><td><span class="' . ($isExpired ? 'error' : 'success') . '">' . ($isExpired ? '❌ YES' : '✅ NO') . '</span></td></tr>';
        }
        
        echo '<tr><td>Is Valid?</td><td><span class="' . ($isValid ? 'success' : 'error') . '">' . ($isValid ? '✅ YES' : '❌ NO') . '</span></td></tr>';
        echo '<tr><td>Plan ID</td><td>' . ($subscription->plan_id ?? 'N/A') . '</td></tr>';
        echo '</table>';
    } else {
        echo '<p class="error">❌ No active subscription found</p>';
        echo '<p class="info">Query used: whereIn(\'status\', [\'trial\', \'active\'])->valid()->latest()</p>';
    }
} else {
    echo '<p class="warning">⚠️ Skipped (no organization)</p>';
}
echo '</div>';

// Test 3: Check plan and features
echo '<div class="section">';
echo '<h2>3️⃣ Plan & Features Check</h2>';

if ($org && isset($subscription) && $subscription) {
    $plan = $subscription->plan;
    
    if ($plan) {
        echo '<p class="success">✅ Plan found: ' . htmlspecialchars($plan->name) . '</p>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        echo '<tr><td>ID</td><td>' . $plan->id . '</td></tr>';
        echo '<tr><td>Name</td><td>' . htmlspecialchars($plan->name) . '</td></tr>';
        echo '<tr><td>Slug</td><td>' . htmlspecialchars($plan->slug ?? 'N/A') . '</td></tr>';
        echo '</table>';
        
        // Check webhook feature
        echo '<h3 style="color: #4ec9b0; margin-top: 20px;">Feature: enable_webhooks</h3>';
        $webhookFeature = $plan->features()->where('feature_key', 'enable_webhooks')->first();
        
        if ($webhookFeature) {
            echo '<p class="success">✅ Webhook feature found</p>';
            echo '<table>';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            echo '<tr><td>ID</td><td>' . $webhookFeature->id . '</td></tr>';
            echo '<tr><td>Feature Key</td><td>' . htmlspecialchars($webhookFeature->feature_key) . '</td></tr>';
            echo '<tr><td>Feature Type</td><td><span class="badge badge-' . ($webhookFeature->feature_type == 'boolean' ? 'success' : 'warning') . '">' . strtoupper($webhookFeature->feature_type) . '</span></td></tr>';
            echo '<tr><td>Feature Value (raw)</td><td><pre>' . htmlspecialchars(json_encode($webhookFeature->feature_value, JSON_PRETTY_PRINT)) . '</pre></td></tr>';
            
            if ($webhookFeature->feature_type == 'boolean') {
                $enabled = $webhookFeature->feature_value['enabled'] ?? false;
                echo '<tr><td>Enabled?</td><td><span class="' . ($enabled ? 'success' : 'error') . '">' . ($enabled ? '✅ YES' : '❌ NO') . '</span></td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="error">❌ Webhook feature NOT found in plan</p>';
            echo '<p class="info">This plan does not have enable_webhooks feature</p>';
        }
        
        // List all features
        echo '<h3 style="color: #4ec9b0; margin-top: 20px;">All Features in Plan</h3>';
        $allFeatures = $plan->features;
        if ($allFeatures->count() > 0) {
            echo '<table>';
            echo '<tr><th>Feature Key</th><th>Type</th><th>Value</th></tr>';
            foreach ($allFeatures as $feature) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($feature->feature_key) . '</td>';
                echo '<td>' . htmlspecialchars($feature->feature_type) . '</td>';
                echo '<td><pre style="margin:0">' . htmlspecialchars(json_encode($feature->feature_value, JSON_PRETTY_PRINT)) . '</pre></td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">⚠️ No features found in this plan</p>';
        }
    } else {
        echo '<p class="error">❌ Plan not found</p>';
    }
} else {
    echo '<p class="warning">⚠️ Skipped (no subscription)</p>';
}
echo '</div>';

// Test 4: Check service result
echo '<div class="section">';
echo '<h2>4️⃣ WebhooksPermissionService Test</h2>';

if ($org) {
    try {
        $service = app(\App\Services\WebhooksPermissionService::class);
        $canUseSepay = $service->canUseSepay($org->id);
        $canUseWebhooks = $service->canUseWebhooks($org->id);
        
        echo '<table>';
        echo '<tr><th>Method</th><th>Result</th></tr>';
        echo '<tr><td>canUseWebhooks()</td><td><span class="' . ($canUseWebhooks ? 'success' : 'error') . '">' . ($canUseWebhooks ? '✅ TRUE' : '❌ FALSE') . '</span></td></tr>';
        echo '<tr><td>canUseSepay()</td><td><span class="' . ($canUseSepay ? 'success' : 'error') . '">' . ($canUseSepay ? '✅ TRUE' : '❌ FALSE') . '</span></td></tr>';
        echo '</table>';
        
        if (!$canUseSepay) {
            echo '<p class="error">❌ Organization CANNOT use Sepay payment method</p>';
            echo '<p class="info">Sepay payment option will NOT be displayed</p>';
        } else {
            echo '<p class="success">✅ Organization CAN use Sepay payment method</p>';
            echo '<p class="info">Sepay payment option WILL be displayed</p>';
        }
    } catch (\Exception $e) {
        echo '<p class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
} else {
    echo '<p class="warning">⚠️ Skipped (no organization)</p>';
}
echo '</div>';

// Test 5: Check Sepay config from .env
echo '<div class="section">';
echo '<h2>5️⃣ Sepay Config (.env)</h2>';

$sepayConfig = [
    'bank_name' => config('services.sepay.bank_name'),
    'account_number' => config('services.sepay.account_number'),
    'account_name' => config('services.sepay.account_name'),
    'branch' => config('services.sepay.branch'),
    'api_key' => config('services.sepay.api_key') ? '***HIDDEN***' : null,
];

echo '<table>';
echo '<tr><th>Config Key</th><th>Value</th><th>Status</th></tr>';
foreach ($sepayConfig as $key => $value) {
    $hasValue = !empty($value);
    echo '<tr>';
    echo '<td>services.sepay.' . $key . '</td>';
    echo '<td>' . ($value ? htmlspecialchars($value) : '<span class="warning">NULL</span>') . '</td>';
    echo '<td><span class="' . ($hasValue ? 'success' : 'error') . '">' . ($hasValue ? '✅' : '❌') . '</span></td>';
    echo '</tr>';
}
echo '</table>';

$configComplete = !empty($sepayConfig['bank_name']) && !empty($sepayConfig['account_number']) && !empty($sepayConfig['account_name']);
if ($configComplete) {
    echo '<p class="success">✅ Sepay config is complete</p>';
} else {
    echo '<p class="error">❌ Sepay config is incomplete - missing required fields</p>';
}
echo '</div>';

// Test 6: Check organization banking
echo '<div class="section">';
echo '<h2>6️⃣ Organization Banking</h2>';

if ($org) {
    $orgBanking = \App\Models\OrganizationBanking::where('organization_id', $org->id)
        ->where('status', 'active')
        ->whereNull('deleted_at')
        ->first();
    
    if ($orgBanking) {
        echo '<p class="success">✅ Organization banking found</p>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        echo '<tr><td>ID</td><td>' . $orgBanking->id . '</td></tr>';
        echo '<tr><td>Bank Name</td><td>' . htmlspecialchars($orgBanking->bank_name ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Account Number</td><td>' . htmlspecialchars($orgBanking->account_number ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Account Name</td><td>' . htmlspecialchars($orgBanking->account_name ?? 'N/A') . '</td></tr>';
        echo '<tr><td>Status</td><td>' . ($orgBanking->status ?? 'N/A') . '</td></tr>';
        echo '</table>';
        echo '<p class="info">Bank QR payment method WILL be displayed</p>';
    } else {
        echo '<p class="error">❌ No active organization banking found</p>';
        echo '<p class="info">Bank QR payment method will NOT be displayed</p>';
    }
} else {
    echo '<p class="warning">⚠️ Skipped (no organization)</p>';
}
echo '</div>';

// Test 7: Invoice check
if ($invoiceId) {
    echo '<div class="section">';
    echo '<h2>7️⃣ Invoice Check</h2>';
    
    $invoice = \App\Models\Invoice::find($invoiceId);
    
    if ($invoice) {
        echo '<p class="success">✅ Invoice found</p>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th></tr>';
        echo '<tr><td>ID</td><td>' . $invoice->id . '</td></tr>';
        echo '<tr><td>Invoice No</td><td>' . htmlspecialchars($invoice->invoice_no) . '</td></tr>';
        echo '<tr><td>Status</td><td><span class="badge badge-' . ($invoice->status == 'paid' ? 'success' : 'warning') . '">' . strtoupper($invoice->status) . '</span></td></tr>';
        echo '<tr><td>Total Amount</td><td>' . number_format($invoice->total_amount, 0, ',', '.') . ' VNĐ</td></tr>';
        echo '<tr><td>Organization ID</td><td>' . ($invoice->organization_id ?? 'N/A') . '</td></tr>';
        echo '</table>';
        
        if ($invoice->organization_id) {
            if ($invoice->organization_id == $orgId) {
                echo '<p class="success">✅ Invoice belongs to tested organization</p>';
            } else {
                echo '<p class="warning">⚠️ Invoice belongs to different organization (ID: ' . $invoice->organization_id . ')</p>';
            }
        }
    } else {
        echo '<p class="error">❌ Invoice not found with ID: ' . $invoiceId . '</p>';
    }
    
    echo '</div>';
}

// Test 8: Summary
echo '<div class="section">';
echo '<h2>8️⃣ Payment Methods Summary</h2>';

if ($org) {
    $hasOrgBanking = isset($orgBanking) && $orgBanking;
    $hasSepayPermission = isset($canUseSepay) && $canUseSepay;
    
    echo '<h3 style="color: #4ec9b0;">Expected Payment Methods to Display:</h3>';
    echo '<table>';
    echo '<tr><th>Method</th><th>Display?</th><th>Reason</th></tr>';
    echo '<tr><td>💵 Tiền mặt (Cash)</td><td><span class="success">✅ YES</span></td><td>Always displayed</td></tr>';
    
    echo '<tr><td>🏦 Chuyển khoản trực tuyến (Bank QR)</td><td>';
    if ($hasOrgBanking) {
        echo '<span class="success">✅ YES</span></td><td>Organization has active banking</td>';
    } else {
        echo '<span class="error">❌ NO</span></td><td>No active organization banking</td>';
    }
    echo '</tr>';
    
    echo '<tr><td>🏛️ Chuyển khoản qua SePay</td><td>';
    if ($hasSepayPermission) {
        echo '<span class="success">✅ YES</span></td><td>Organization has webhook permission</td>';
    } else {
        echo '<span class="error">❌ NO</span></td><td>Organization does NOT have webhook permission</td>';
    }
    echo '</tr>';
    echo '</table>';
    
    $totalMethods = 1 + ($hasOrgBanking ? 1 : 0) + ($hasSepayPermission ? 1 : 0);
    echo '<p style="font-size: 18px; margin-top: 20px;"><strong>Total Expected: ' . $totalMethods . ' payment method(s)</strong></p>';
    
    if ($totalMethods == 1) {
        echo '<p class="error">⚠️ Only 1 payment method (Cash) will be displayed!</p>';
        echo '<p class="info">To fix: Enable organization banking OR enable webhook permission</p>';
    } else {
        echo '<p class="success">✅ Multiple payment methods will be displayed</p>';
    }
}
echo '</div>';

// Test 9: SQL Query to check
echo '<div class="section">';
echo '<h2>9️⃣ SQL Debug Query</h2>';

if ($orgId) {
    $sql = "
SELECT 
    o.id as org_id,
    o.name as org_name,
    os.id as subscription_id,
    os.status as sub_status,
    os.current_period_end,
    sp.id as plan_id,
    sp.name as plan_name,
    pf.feature_key,
    pf.feature_type,
    pf.feature_value,
    CASE 
        WHEN os.status IN ('active', 'trial') 
             AND (os.current_period_end IS NULL OR os.current_period_end > NOW())
             AND pf.feature_key = 'enable_webhooks'
             AND pf.feature_type = 'boolean'
             AND JSON_EXTRACT(pf.feature_value, '$.enabled') = true
        THEN 'HAS_SEPAY'
        ELSE 'NO_SEPAY'
    END as sepay_status
FROM organizations o
LEFT JOIN organization_subscriptions os ON os.organization_id = o.id 
    AND os.status IN ('active', 'trial')
    AND (os.current_period_end IS NULL OR os.current_period_end > NOW())
LEFT JOIN subscription_plans sp ON sp.id = os.plan_id
LEFT JOIN plan_features pf ON pf.plan_id = sp.id AND pf.feature_key = 'enable_webhooks'
WHERE o.id = {$orgId}
ORDER BY os.created_at DESC
LIMIT 1;
";
    
    echo '<p>Run this query in your database to verify:</p>';
    echo '<pre>' . htmlspecialchars($sql) . '</pre>';
    
    // Execute and show result
    try {
        $result = DB::select($sql);
        if (!empty($result)) {
            echo '<h3 style="color: #4ec9b0;">Query Result:</h3>';
            echo '<table>';
            $first = true;
            foreach ($result as $row) {
                if ($first) {
                    echo '<tr>';
                    foreach ($row as $key => $value) {
                        echo '<th>' . htmlspecialchars($key) . '</th>';
                    }
                    echo '</tr>';
                    $first = false;
                }
                echo '<tr>';
                foreach ($row as $key => $value) {
                    if ($key == 'sepay_status') {
                        $class = $value == 'HAS_SEPAY' ? 'success' : 'error';
                        echo '<td><span class="' . $class . '">' . htmlspecialchars($value) . '</span></td>';
                    } else {
                        echo '<td>' . htmlspecialchars($value ?? 'NULL') . '</td>';
                    }
                }
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">⚠️ Query returned no results</p>';
        }
    } catch (\Exception $e) {
        echo '<p class="error">❌ Query Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
echo '</div>';

<?php else: ?>

<div class="section">
    <h2>ℹ️ Instructions</h2>
    <p>Please provide Organization ID or Invoice ID to start testing.</p>
    <p>Example: <code>test.php?org_id=1&invoice_id=1</code></p>
</div>

<div class="section">
    <h2>🔍 Find Your IDs</h2>
    <h3>Organizations (Latest 10):</h3>
    <?php
    $orgs = \App\Models\Organization::orderBy('id', 'desc')->limit(10)->get();
    if ($orgs->count() > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Name</th><th>Code</th><th>Test Link</th></tr>';
        foreach ($orgs as $org) {
            echo '<tr>';
            echo '<td>' . $org->id . '</td>';
            echo '<td>' . htmlspecialchars($org->name) . '</td>';
            echo '<td>' . htmlspecialchars($org->organization_code ?? 'N/A') . '</td>';
            echo '<td><a href="?org_id=' . $org->id . '" style="color: #569cd6;">Test</a></td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No organizations found</p>';
    }
    ?>
    
    <h3 style="margin-top: 20px;">Invoices (Latest 10):</h3>
    <?php
    $invoices = \App\Models\Invoice::orderBy('id', 'desc')->limit(10)->get();
    if ($invoices->count() > 0) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Invoice No</th><th>Status</th><th>Org ID</th><th>Test Link</th></tr>';
        foreach ($invoices as $inv) {
            echo '<tr>';
            echo '<td>' . $inv->id . '</td>';
            echo '<td>' . htmlspecialchars($inv->invoice_no) . '</td>';
            echo '<td>' . htmlspecialchars($inv->status) . '</td>';
            echo '<td>' . ($inv->organization_id ?? 'N/A') . '</td>';
            echo '<td><a href="?org_id=' . $inv->organization_id . '&invoice_id=' . $inv->id . '" style="color: #569cd6;">Test</a></td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No invoices found</p>';
    }
    ?>
</div>

<?php endif; ?>

<div class="section">
    <h2>📝 Notes</h2>
    <ul>
        <li>This test file checks all conditions required for Sepay payment method to display</li>
        <li>Delete this file after debugging for security</li>
        <li>Check Laravel logs at <code>storage/logs/laravel.log</code> for more details</li>
    </ul>
</div>

</body>
</html>

