<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Vouchers</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            background: #fff;
        }
        
        .voucher-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            padding: 10px;
        }
        
        .voucher {
            border: 2px dashed #333;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            page-break-inside: avoid;
        }
        
        .voucher-header {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #0891b2;
        }
        
        .voucher-profile {
            font-size: 12px;
            color: #666;
            margin-bottom: 8px;
        }
        
        .voucher-credentials {
            background: #f3f4f6;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .voucher-label {
            font-size: 10px;
            color: #666;
            text-transform: uppercase;
        }
        
        .voucher-value {
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 4px 0;
        }
        
        .voucher-footer {
            font-size: 10px;
            color: #999;
            margin-top: 10px;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .no-print {
                display: none;
            }
            
            .voucher-grid {
                gap: 5px;
                padding: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 20px; background: #f3f4f6; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 30px; background: #0891b2; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px;">
            🖨️ Print Vouchers
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; background: #6b7280; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-left: 10px;">
            ✕ Close
        </button>
        <p style="margin-top: 10px; color: #666;">Total: {{ count($vouchers) }} vouchers</p>
    </div>

    <div class="voucher-grid">
        @foreach($vouchers as $voucher)
        <div class="voucher">
            <div class="voucher-header">🌐 HOTSPOT VOUCHER</div>
            <div class="voucher-profile">
                {{ is_array($voucher) ? $voucher['profile'] : $voucher->profile_name }}
                @if(isset($profile) && $profile->validity)
                    ({{ $profile->validity }})
                @endif
            </div>
            <div class="voucher-credentials">
                <div class="voucher-label">Username</div>
                <div class="voucher-value">{{ is_array($voucher) ? $voucher['username'] : $voucher->username }}</div>
                <div class="voucher-label" style="margin-top: 8px;">Password</div>
                <div class="voucher-value">{{ is_array($voucher) ? $voucher['password'] : $voucher->password }}</div>
            </div>
            <div class="voucher-footer">
                Connect to WiFi → Open browser → Login
            </div>
        </div>
        @endforeach
    </div>
</body>
</html>
