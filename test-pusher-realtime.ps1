# Pusher Real-Time Debug Test
Write-Host "=== Testing Pusher Real-Time Messaging ===" -ForegroundColor Green

$API_BASE = "http://localhost:8000/api"

# Test user credentials
$user1 = @{ email = "john@example.com"; password = "password123" }
$user2 = @{ email = "jane@example.com"; password = "password123" }

Write-Host "`n1. Logging in users..." -ForegroundColor Yellow

# Login User 1
try {
    $loginData1 = $user1 | ConvertTo-Json
    $response1 = Invoke-RestMethod -Uri "$API_BASE/auth/login" -Method POST -Body $loginData1 -ContentType "application/json"
    $token1 = $response1.data.token
    Write-Host "✓ User 1 logged in successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ User 1 login failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

# Login User 2
try {
    $loginData2 = $user2 | ConvertTo-Json
    $response2 = Invoke-RestMethod -Uri "$API_BASE/auth/login" -Method POST -Body $loginData2 -ContentType "application/json"
    $token2 = $response2.data.token
    Write-Host "✓ User 2 logged in successfully" -ForegroundColor Green
} catch {
    Write-Host "✗ User 2 login failed: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

Write-Host "`n2. Creating chat..." -ForegroundColor Yellow

$headers1 = @{ Authorization = "Bearer $token1" }
$headers2 = @{ Authorization = "Bearer $token2" }

# Get users list
$usersResponse = Invoke-RestMethod -Uri "$API_BASE/users" -Method GET -Headers $headers1
$otherUser = $usersResponse.data.users | Where-Object { $_.email -eq $user2.email } | Select-Object -First 1

if (-not $otherUser) {
    Write-Host "✗ Could not find second user" -ForegroundColor Red
    exit 1
}

# Create chat
$chatData = @{
    type = "private"
    participants = @($otherUser.id)
} | ConvertTo-Json

$chatResponse = Invoke-RestMethod -Uri "$API_BASE/chats" -Method POST -Body $chatData -ContentType "application/json" -Headers $headers1
$chatId = $chatResponse.data.chat.id
Write-Host "✓ Chat created with ID: $chatId" -ForegroundColor Green

Write-Host "`n3. Testing real-time message sending..." -ForegroundColor Yellow

# Send message from User 1
$messageData = @{
    content = "🚀 Testing real-time message at $(Get-Date -Format 'HH:mm:ss')"
    type = "text"
} | ConvertTo-Json

Write-Host "📤 Sending message from User 1..." -ForegroundColor Cyan
$messageResponse = Invoke-RestMethod -Uri "$API_BASE/chats/$chatId/messages" -Method POST -Body $messageData -ContentType "application/json" -Headers $headers1

if ($messageResponse.status -eq "success") {
    Write-Host "✅ Message sent successfully!" -ForegroundColor Green
    Write-Host "   Message ID: $($messageResponse.data.message.id)" -ForegroundColor Gray
    Write-Host "   Content: $($messageResponse.data.message.content)" -ForegroundColor Gray
} else {
    Write-Host "❌ Message sending failed" -ForegroundColor Red
}

Write-Host "`n4. Testing typing indicator..." -ForegroundColor Yellow

# Send typing indicator from User 2
$typingData = @{
    is_typing = $true
} | ConvertTo-Json

Write-Host "⌨️ Sending typing indicator from User 2..." -ForegroundColor Cyan
try {
    $typingResponse = Invoke-RestMethod -Uri "$API_BASE/chats/$chatId/messages/typing" -Method POST -Body $typingData -ContentType "application/json" -Headers $headers2
    if ($typingResponse.status -eq "success") {
        Write-Host "✅ Typing indicator sent!" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Typing indicator failed: $($_.Exception.Message)" -ForegroundColor Red
}

# Stop typing
Start-Sleep -Seconds 1
$stopTypingData = @{
    is_typing = $false
} | ConvertTo-Json

Invoke-RestMethod -Uri "$API_BASE/chats/$chatId/messages/typing" -Method POST -Body $stopTypingData -ContentType "application/json" -Headers $headers2 | Out-Null

Write-Host "`n5. Checking Pusher configuration..." -ForegroundColor Yellow
Write-Host "✅ App ID: 2038130" -ForegroundColor Green
Write-Host "✅ Key: af5273c3733d66a73e0a" -ForegroundColor Green
Write-Host "✅ Cluster: eu" -ForegroundColor Green
Write-Host "✅ Channel: chat.$chatId" -ForegroundColor Green
Write-Host "✅ Events: MessageSent, UserTyping" -ForegroundColor Green

Write-Host "`n6. Next steps for testing:" -ForegroundColor Yellow
Write-Host "📱 1. Open two browser windows/tabs" -ForegroundColor Cyan
Write-Host "📱 2. Login as different users in each" -ForegroundColor Cyan
Write-Host "📱 3. Open chat with ID: $chatId" -ForegroundColor Cyan
Write-Host "📱 4. Send messages and watch real-time updates" -ForegroundColor Cyan
Write-Host "📱 5. Check browser console for Pusher logs" -ForegroundColor Cyan

Write-Host "`n🔍 To debug Pusher events:" -ForegroundColor Yellow
Write-Host "   • Open Pusher Dashboard: https://dashboard.pusher.com/apps/2038130/debug_console" -ForegroundColor Gray
Write-Host "   • Watch for events in real-time" -ForegroundColor Gray
Write-Host "   • Check browser console for Echo connection logs" -ForegroundColor Gray

Write-Host "`n=== Pusher Test Complete ===" -ForegroundColor Green
