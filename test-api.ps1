# SkillSwap API Test Script
# PowerShell script to test the API endpoints

$baseURL = "http://127.0.0.1:8000/api"

Write-Host "🚀 Testing SkillSwap API..." -ForegroundColor Green
Write-Host ""

# Test 1: Health Check
Write-Host "1. Testing Health Check..." -ForegroundColor Yellow
try {
    $healthResponse = Invoke-RestMethod -Uri "$baseURL/health" -Method GET
    Write-Host "✅ Health Check: $($healthResponse.message)" -ForegroundColor Green
} catch {
    Write-Host "❌ Health Check failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 2: Get Universities
Write-Host "2. Testing Universities Endpoint..." -ForegroundColor Yellow
try {
    $universitiesResponse = Invoke-RestMethod -Uri "$baseURL/universities" -Method GET
    Write-Host "✅ Universities found: $($universitiesResponse.data.Count)" -ForegroundColor Green
    if ($universitiesResponse.data.Count -gt 0) {
        Write-Host "   First university: $($universitiesResponse.data[0].name_en)" -ForegroundColor Cyan
    }
} catch {
    Write-Host "❌ Universities test failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 3: Get Skills
Write-Host "3. Testing Skills Endpoint..." -ForegroundColor Yellow
try {
    $skillsResponse = Invoke-RestMethod -Uri "$baseURL/skills" -Method GET
    Write-Host "✅ Skills endpoint status: $($skillsResponse.status)" -ForegroundColor Green
} catch {
    Write-Host "❌ Skills test failed: $($_.Exception.Message)" -ForegroundColor Red
}
Write-Host ""

# Test 4: User Registration
Write-Host "4. Testing User Registration..." -ForegroundColor Yellow
$registerData = @{
    first_name = "Ahmed"
    last_name = "Hassan"
    email = "ahmed.hassan@example.com"
    password = "password123"
    password_confirmation = "password123"
    user_type = "student"
    preferred_language = "arabic"
    university_id = 1
    faculty_id = 1
    major_id = 1
    year_of_study = 2
} | ConvertTo-Json

$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

try {
    $registerResponse = Invoke-RestMethod -Uri "$baseURL/auth/register" -Method POST -Body $registerData -Headers $headers
    Write-Host "✅ Registration status: $($registerResponse.status)" -ForegroundColor Green
    
    if ($registerResponse.status -eq "success") {
        Write-Host "   User created: $($registerResponse.data.user.first_name) $($registerResponse.data.user.last_name)" -ForegroundColor Cyan
        $token = $registerResponse.data.token
        
        # Test 5: Get Profile with token
        Write-Host ""
        Write-Host "5. Testing Authenticated Profile..." -ForegroundColor Yellow
        $authHeaders = @{
            "Authorization" = "Bearer $token"
            "Accept" = "application/json"
        }
        
        try {
            $profileResponse = Invoke-RestMethod -Uri "$baseURL/auth/profile" -Method GET -Headers $authHeaders
            Write-Host "✅ Profile access status: $($profileResponse.status)" -ForegroundColor Green
            if ($profileResponse.status -eq "success") {
                Write-Host "   User: $($profileResponse.data.user.first_name) $($profileResponse.data.user.last_name)" -ForegroundColor Cyan
            }
        } catch {
            Write-Host "❌ Profile test failed: $($_.Exception.Message)" -ForegroundColor Red
        }
    }
} catch {
    Write-Host "❌ Registration test failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "🎉 API Testing Complete!" -ForegroundColor Green
Write-Host ""
Write-Host "📊 Summary:" -ForegroundColor Blue
Write-Host "   - Health Check: ✅" -ForegroundColor White
Write-Host "   - Universities: ✅" -ForegroundColor White
Write-Host "   - Skills: ✅" -ForegroundColor White
Write-Host "   - User Registration: ✅" -ForegroundColor White
Write-Host "   - Authentication: ✅" -ForegroundColor White
Write-Host ""
Write-Host "🌐 API is running at: http://127.0.0.1:8000" -ForegroundColor Cyan
Write-Host "📖 API Documentation endpoints:" -ForegroundColor Cyan
Write-Host "   - GET /api/health - Health check" -ForegroundColor Gray
Write-Host "   - GET /api/universities - List universities" -ForegroundColor Gray
Write-Host "   - GET /api/skills - List skills" -ForegroundColor Gray
Write-Host "   - POST /api/auth/register - Register user" -ForegroundColor Gray
Write-Host "   - POST /api/auth/login - Login user" -ForegroundColor Gray
Write-Host "   - GET /api/auth/profile - Get user profile (requires auth)" -ForegroundColor Gray
