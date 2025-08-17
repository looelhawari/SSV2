# Test Notification Creation
$baseURL = "http://127.0.0.1:8000/api"

Write-Host "Testing Notification System..." -ForegroundColor Green

# Create a test user
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$userData = @{
    first_name = "NotifTest"
    last_name = "User"
    email = "notiftest_$timestamp@example.com"
    password = "password123"
    password_confirmation = "password123"
    user_type = "student"
    preferred_language = "english"
    university_id = 1
    faculty_id = 1
    major_id = 1
    year_of_study = 2
} | ConvertTo-Json

try {
    $userResponse = Invoke-RestMethod -Uri "$baseURL/auth/register" -Method POST -Body $userData -Headers $headers
    Write-Host "Test user created: $($userResponse.data.user.first_name)" -ForegroundColor Green
    $userToken = $userResponse.data.token
    $userId = $userResponse.data.user.id
    
    $authHeaders = @{
        "Authorization" = "Bearer $userToken"
        "Accept" = "application/json"
    }
    
    # Try to get notifications for this user
    try {
        $notificationsResponse = Invoke-RestMethod -Uri "$baseURL/notifications" -Method GET -Headers $authHeaders
        Write-Host "Notifications endpoint working!" -ForegroundColor Green
        Write-Host "User has: $($notificationsResponse.data.Count) notifications" -ForegroundColor Cyan
    } catch {
        Write-Host "Notifications endpoint failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
    
} catch {
    Write-Host "User creation failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host "Notification test complete!" -ForegroundColor Green
