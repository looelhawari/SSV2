# SkillSwap Friend Request Test Script
$baseURL = "http://127.0.0.1:8000/api"

Write-Host "Testing Friend Request Functionality..." -ForegroundColor Green
Write-Host ""

# First, create two test users for friend request testing
Write-Host "1. Creating Test Users..." -ForegroundColor Yellow

$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

# Create User 1
$timestamp = Get-Date -Format "yyyyMMddHHmmss"
$user1Data = @{
    first_name = "Test"
    last_name = "User1"
    email = "testuser1_$timestamp@example.com"
    password = "password123"
    password_confirmation = "password123"
    user_type = "student"
    preferred_language = "english"
    university_id = 1
    faculty_id = 1
    major_id = 1
    year_of_study = 2
} | ConvertTo-Json

# Create User 2
$user2Data = @{
    first_name = "Test"
    last_name = "User2"
    email = "testuser2_$timestamp@example.com"
    password = "password123"
    password_confirmation = "password123"
    user_type = "student"
    preferred_language = "english"
    university_id = 1
    faculty_id = 1
    major_id = 1
    year_of_study = 3
} | ConvertTo-Json

try {
    # Register User 1
    $user1Response = Invoke-RestMethod -Uri "$baseURL/auth/register" -Method POST -Body $user1Data -Headers $headers
    Write-Host "User 1 created: $($user1Response.data.user.first_name)" -ForegroundColor Green
    $user1Token = $user1Response.data.token
    $user1Id = $user1Response.data.user.id
    
    # Register User 2  
    $user2Response = Invoke-RestMethod -Uri "$baseURL/auth/register" -Method POST -Body $user2Data -Headers $headers
    Write-Host "User 2 created: $($user2Response.data.user.first_name)" -ForegroundColor Green
    $user2Token = $user2Response.data.token
    $user2Id = $user2Response.data.user.id
    
    Write-Host ""
    Write-Host "2. Testing User Search..." -ForegroundColor Yellow
    
    # Test user search (as User 1)
    $authHeaders1 = @{
        "Authorization" = "Bearer $user1Token"
        "Accept" = "application/json"
    }
    
    try {
        $searchResponse = Invoke-RestMethod -Uri "$baseURL/users?q=User2" -Method GET -Headers $authHeaders1
        Write-Host "User search successful. Found: $($searchResponse.data.Count) users" -ForegroundColor Green
        
        if ($searchResponse.data.Count -gt 0) {
            Write-Host "   Found user: $($searchResponse.data[0].first_name) $($searchResponse.data[0].last_name)" -ForegroundColor Cyan
        }
    } catch {
        Write-Host "User search failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "   Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }
    
    Write-Host ""
    Write-Host "3. Testing Friend Request..." -ForegroundColor Yellow
    
    # Send friend request from User 1 to User 2
    try {
        $friendRequestResponse = Invoke-RestMethod -Uri "$baseURL/users/$user2Id/friend-request" -Method POST -Headers $authHeaders1
        Write-Host "Friend request sent successfully!" -ForegroundColor Green
        Write-Host "   Response: $($friendRequestResponse.message)" -ForegroundColor Cyan
        
        # Check if User 2 received a notification
        Write-Host ""
        Write-Host "3.1. Checking notifications for User 2..." -ForegroundColor Yellow
        
        $authHeaders2 = @{
            "Authorization" = "Bearer $user2Token"
            "Accept" = "application/json"
        }
        
        try {
            $notificationsResponse = Invoke-RestMethod -Uri "$baseURL/notifications" -Method GET -Headers $authHeaders2
            Write-Host "Notifications check successful!" -ForegroundColor Green
            Write-Host "   User 2 has: $($notificationsResponse.data.Count) notifications" -ForegroundColor Cyan
            
            if ($notificationsResponse.data.Count -gt 0) {
                $friendRequestNotification = $notificationsResponse.data | Where-Object { $_.type -eq "friend_request" }
                if ($friendRequestNotification) {
                    Write-Host "   Found friend request notification: $($friendRequestNotification.message)" -ForegroundColor Green
                } else {
                    Write-Host "   No friend request notification found" -ForegroundColor Red
                }
            }
        } catch {
            Write-Host "Failed to check notifications: $($_.Exception.Message)" -ForegroundColor Red
        }
        
    } catch {
        Write-Host "Friend request failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "   Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
        if ($_.Exception.Response.StatusCode -eq 500) {
            Write-Host "   500 Internal Server Error - Check Laravel logs" -ForegroundColor Red
        }
    }
    
    Write-Host ""
    Write-Host "4. Testing Block User..." -ForegroundColor Yellow
    
    # Test blocking User 2 from User 1
    try {
        $blockResponse = Invoke-RestMethod -Uri "$baseURL/users/$user2Id/block" -Method POST -Headers $authHeaders1
        Write-Host "User blocked successfully!" -ForegroundColor Green
        Write-Host "   Response: $($blockResponse.message)" -ForegroundColor Cyan
    } catch {
        Write-Host "Block user failed: $($_.Exception.Message)" -ForegroundColor Red
        Write-Host "   Status Code: $($_.Exception.Response.StatusCode)" -ForegroundColor Red
    }

} catch {
    Write-Host "Test setup failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "Friend Request Testing Complete!" -ForegroundColor Green
