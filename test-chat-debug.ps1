$API_BASE = "http://localhost:8000/api"

# Test login first
Write-Host "Testing login..." -ForegroundColor Yellow
$loginData = @{
    email = "ali@example.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$API_BASE/auth/login" -Method Post -Body $loginData -ContentType "application/json"
    Write-Host "Login successful!" -ForegroundColor Green
    Write-Host "Token: $($loginResponse.data.token)" -ForegroundColor Cyan
    
    $token = $loginResponse.data.token
    $headers = @{
        'Authorization' = "Bearer $token"
        'Content-Type' = 'application/json'
    }

    # Test fetching users
    Write-Host "`nTesting fetch users..." -ForegroundColor Yellow
    $usersResponse = Invoke-RestMethod -Uri "$API_BASE/users" -Method Get -Headers $headers
    Write-Host "Users response:" -ForegroundColor Green
    $usersResponse | ConvertTo-Json -Depth 3

    # Test fetching chats
    Write-Host "`nTesting fetch chats..." -ForegroundColor Yellow
    $chatsResponse = Invoke-RestMethod -Uri "$API_BASE/chats" -Method Get -Headers $headers
    Write-Host "Chats response:" -ForegroundColor Green
    $chatsResponse | ConvertTo-Json -Depth 3

    # Test creating a chat
    if ($usersResponse.data.users.Count -gt 0) {
        $otherUserId = $usersResponse.data.users[0].id
        Write-Host "`nTesting create chat with user ID: $otherUserId..." -ForegroundColor Yellow
        
        $chatData = @{
            type = "private"
            participants = @($otherUserId)
        } | ConvertTo-Json

        $createChatResponse = Invoke-RestMethod -Uri "$API_BASE/chats" -Method Post -Body $chatData -Headers $headers
        Write-Host "Create chat response:" -ForegroundColor Green
        $createChatResponse | ConvertTo-Json -Depth 3
    }

} catch {
    Write-Host "Error: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $errorResponse = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($errorResponse)
        $errorContent = $reader.ReadToEnd()
        Write-Host "Error details: $errorContent" -ForegroundColor Red
    }
}
