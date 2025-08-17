$apiUrl = "http://localhost:8000/api"

# Test login first to get token
$loginBody = @{
    email = "ayman@test.com"
    password = "password123"
} | ConvertTo-Json

try {
    $loginResponse = Invoke-RestMethod -Uri "$apiUrl/auth/login" -Method POST -Body $loginBody -ContentType "application/json"
    $token = $loginResponse.data.token
    Write-Host "Login successful, token: $($token.Substring(0,20))..."
    
    # Test get users
    $headers = @{
        "Authorization" = "Bearer $token"
        "Content-Type" = "application/json"
    }
    
    Write-Host "`nFetching users..."
    $usersResponse = Invoke-RestMethod -Uri "$apiUrl/users" -Method GET -Headers $headers
    Write-Host "Users response: $($usersResponse | ConvertTo-Json -Depth 3)"
    
    # Test get chats
    Write-Host "`nFetching chats..."
    $chatsResponse = Invoke-RestMethod -Uri "$apiUrl/chats" -Method GET -Headers $headers
    Write-Host "Chats response: $($chatsResponse | ConvertTo-Json -Depth 3)"
    
    # Test create chat if users exist
    if ($usersResponse.data.users -and $usersResponse.data.users.Count -gt 0) {
        $firstUser = $usersResponse.data.users[0]
        Write-Host "`nTrying to create chat with user: $($firstUser.first_name) $($firstUser.last_name)"
        
        $chatBody = @{
            type = "private"
            participants = @($firstUser.id)
        } | ConvertTo-Json
        
        $createChatResponse = Invoke-RestMethod -Uri "$apiUrl/chats" -Method POST -Body $chatBody -Headers $headers
        Write-Host "Create chat response: $($createChatResponse | ConvertTo-Json -Depth 3)"
    }
    
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    if ($_.Exception.Response) {
        $errorStream = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($errorStream)
        $errorBody = $reader.ReadToEnd()
        Write-Host "Error body: $errorBody"
    }
}
