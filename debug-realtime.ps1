#!/usr/bin/env powershell

Write-Host "=== Debugging Real-time Chat Issues ===" -ForegroundColor Green

# 1. Test Pusher connection
Write-Host "`n1. Testing Pusher Configuration..." -ForegroundColor Yellow
$API_BASE = "http://localhost:8000/api"

# First, let's login and get a token
try {
    $loginData = @{
        email = "john@example.com"
        password = "password123"
    } | ConvertTo-Json

    $response = Invoke-RestMethod -Uri "$API_BASE/auth/login" -Method POST -Body $loginData -ContentType "application/json"
    
    if ($response.status -eq "success") {
        $token = $response.data.token
        Write-Host "✓ Login successful, token: $($token.Substring(0,20))..." -ForegroundColor Green
        
        # 2. Test broadcasting auth endpoint
        Write-Host "`n2. Testing Broadcasting Auth..." -ForegroundColor Yellow
        $headers = @{ 
            Authorization = "Bearer $token"
            'Content-Type' = 'application/json'
        }
        
        # Test the broadcasting auth endpoint
        try {
            $authResponse = Invoke-RestMethod -Uri "http://localhost:8000/broadcasting/auth" -Method POST -Headers $headers -Body "{}"
            Write-Host "✓ Broadcasting auth endpoint accessible" -ForegroundColor Green
        } catch {
            Write-Host "✗ Broadcasting auth failed: $($_.Exception.Message)" -ForegroundColor Red
            Write-Host "  This might be why real-time isn't working!" -ForegroundColor Yellow
        }
        
        # 3. Check if we can send a message and trigger broadcast
        Write-Host "`n3. Testing Message Broadcasting..." -ForegroundColor Yellow
        
        # Get user list first
        $usersResponse = Invoke-RestMethod -Uri "$API_BASE/users" -Method GET -Headers $headers
        
        if ($usersResponse.status -eq "success" -and $usersResponse.data.users.Count -gt 0) {
            $otherUser = $usersResponse.data.users | Where-Object { $_.email -ne "john@example.com" } | Select-Object -First 1
            
            if ($otherUser) {
                # Create or get existing chat
                $chatData = @{
                    type = "private"
                    participants = @($otherUser.id)
                } | ConvertTo-Json
                
                $chatResponse = Invoke-RestMethod -Uri "$API_BASE/chats" -Method POST -Body $chatData -ContentType "application/json" -Headers $headers
                
                if ($chatResponse.status -eq "success") {
                    $chatId = $chatResponse.data.chat.id
                    Write-Host "✓ Chat ready (ID: $chatId)" -ForegroundColor Green
                    
                    # Send a test message
                    $messageData = @{
                        content = "Test real-time message - $(Get-Date)"
                        type = "text"
                    } | ConvertTo-Json
                    
                    $messageResponse = Invoke-RestMethod -Uri "$API_BASE/chats/$chatId/messages" -Method POST -Body $messageData -ContentType "application/json" -Headers $headers
                    
                    if ($messageResponse.status -eq "success") {
                        Write-Host "✓ Message sent successfully" -ForegroundColor Green
                        Write-Host "  Message ID: $($messageResponse.data.message.id)" -ForegroundColor Cyan
                        Write-Host "  Check frontend console for real-time reception!" -ForegroundColor Yellow
                    } else {
                        Write-Host "✗ Message sending failed" -ForegroundColor Red
                    }
                    
                    # Test typing indicator
                    Write-Host "`n4. Testing Typing Indicator..." -ForegroundColor Yellow
                    $typingData = @{
                        is_typing = $true
                    } | ConvertTo-Json
                    
                    try {
                        $typingResponse = Invoke-RestMethod -Uri "$API_BASE/chats/$chatId/messages/typing" -Method POST -Body $typingData -ContentType "application/json" -Headers $headers
                        
                        if ($typingResponse.status -eq "success") {
                            Write-Host "✓ Typing indicator sent" -ForegroundColor Green
                        } else {
                            Write-Host "✗ Typing indicator failed: $($typingResponse.message)" -ForegroundColor Red
                        }
                    } catch {
                        Write-Host "✗ Typing indicator error: $($_.Exception.Message)" -ForegroundColor Red
                    }
                    
                } else {
                    Write-Host "✗ Chat creation failed" -ForegroundColor Red
                }
            } else {
                Write-Host "✗ No other users found" -ForegroundColor Red
            }
        } else {
            Write-Host "✗ Could not get users list" -ForegroundColor Red
        }
        
    } else {
        Write-Host "✗ Login failed" -ForegroundColor Red
    }
} catch {
    Write-Host "✗ Login error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "`n5. Debugging Checklist:" -ForegroundColor Yellow
Write-Host "□ Check browser console for Pusher connection logs" -ForegroundColor Cyan
Write-Host "□ Verify BROADCAST_DRIVER=pusher in .env" -ForegroundColor Cyan
Write-Host "□ Confirm Pusher credentials are correct" -ForegroundColor Cyan
Write-Host "□ Check if queue worker is running (php artisan queue:work)" -ForegroundColor Cyan
Write-Host "□ Verify channels.php authorization logic" -ForegroundColor Cyan

Write-Host "`n6. Manual Tests:" -ForegroundColor Yellow
Write-Host "1. Open two browser tabs to http://localhost:3000/chat" -ForegroundColor Cyan
Write-Host "2. Login as different users in each tab" -ForegroundColor Cyan
Write-Host "3. Start a chat between them" -ForegroundColor Cyan
Write-Host "4. Type in one tab and watch the other for typing indicators" -ForegroundColor Cyan
Write-Host "5. Send message in one tab and see if it appears instantly in other" -ForegroundColor Cyan

Write-Host "`n=== Debug Complete ===" -ForegroundColor Green
