const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');
const path = require('path');

// Create a minimal valid JPEG file (1x1 pixel)
function createMinimalJPEG() {
    // This is a minimal valid JPEG file header + data for a 1x1 black pixel
    const jpegHeader = Buffer.from([
        0xFF, 0xD8, 0xFF, 0xE0, 0x00, 0x10, 0x4A, 0x46, 0x49, 0x46, 0x00, 0x01, 0x01, 0x01, 0x00, 0x48,
        0x00, 0x48, 0x00, 0x00, 0xFF, 0xDB, 0x00, 0x43, 0x00, 0x08, 0x06, 0x06, 0x07, 0x06, 0x05, 0x08,
        0x07, 0x07, 0x07, 0x09, 0x09, 0x08, 0x0A, 0x0C, 0x14, 0x0D, 0x0C, 0x0B, 0x0B, 0x0C, 0x19, 0x12,
        0x13, 0x0F, 0x14, 0x1D, 0x1A, 0x1F, 0x1E, 0x1D, 0x1A, 0x1C, 0x1C, 0x20, 0x24, 0x2E, 0x27, 0x20,
        0x22, 0x2C, 0x23, 0x1C, 0x1C, 0x28, 0x37, 0x29, 0x2C, 0x30, 0x31, 0x34, 0x34, 0x34, 0x1F, 0x27,
        0x39, 0x3D, 0x38, 0x32, 0x3C, 0x2E, 0x33, 0x34, 0x32, 0xFF, 0xC0, 0x00, 0x11, 0x08, 0x00, 0x01,
        0x00, 0x01, 0x01, 0x01, 0x11, 0x00, 0x02, 0x11, 0x01, 0x03, 0x11, 0x01, 0xFF, 0xC4, 0x00, 0x14,
        0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
        0x00, 0x08, 0xFF, 0xC4, 0x00, 0x14, 0x10, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
        0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xDA, 0x00, 0x0C, 0x03, 0x01, 0x00, 0x02,
        0x11, 0x03, 0x11, 0x00, 0x3F, 0x00, 0x8A, 0x00, 0xFF, 0xD9
    ]);
    return jpegHeader;
}

// Test credentials
const testUser = {
    email: 'ahmed.hassan@skillswap.eg',
    password: 'password123'
};

const API_BASE = 'http://localhost:8000/api';

async function testAvatarUpload() {
    try {
        console.log('🔑 Logging in...');

        // Login to get token
        const loginResponse = await axios.post(`${API_BASE}/auth/login`, testUser);
        const token = loginResponse.data.data.token;
        console.log('✅ Login successful');

        const authHeaders = {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
        };

        // Get current profile
        console.log('\n📋 Getting current profile...');
        const profileResponse = await axios.get(`${API_BASE}/auth/profile`, {
            headers: authHeaders
        });
        console.log('Current avatar field:', profileResponse.data.data.user.avatar);
        console.log('Current avatar_url:', profileResponse.data.data.user.avatar_url);
        console.log('Current profile completion:', profileResponse.data.data.user.profile_completion + '%');

        // Create a valid JPEG file
        console.log('\n📸 Creating valid JPEG file...');
        const jpegData = createMinimalJPEG();
        const tempImagePath = path.join(__dirname, 'test-avatar.jpg');
        fs.writeFileSync(tempImagePath, jpegData);
        console.log('Created JPEG file with size:', jpegData.length, 'bytes');

        // Test avatar upload
        console.log('\n📤 Testing avatar upload...');
        const formData = new FormData();
        formData.append('avatar', fs.createReadStream(tempImagePath));

        try {
            const avatarResponse = await axios.post(`${API_BASE}/profile/avatar`, formData, {
                headers: {
                    ...authHeaders,
                    ...formData.getHeaders()
                }
            });
            console.log('✅ Avatar upload successful!');
            console.log('Response:', {
                status: avatarResponse.data.status,
                message: avatarResponse.data.message,
                avatar_url: avatarResponse.data.data.avatar_url,
                user_avatar: avatarResponse.data.data.user ? avatarResponse.data.data.user.avatar : 'not included'
            });
        } catch (avatarError) {
            console.log('❌ Avatar upload failed:');
            console.log(avatarError.response?.data || avatarError.message);
        }

        // Verify changes by getting profile again
        console.log('\n🔍 Verifying changes...');
        const finalProfileResponse = await axios.get(`${API_BASE}/auth/profile`, {
            headers: authHeaders
        });
        console.log('Final avatar field:', finalProfileResponse.data.data.user.avatar);
        console.log('Final avatar_url:', finalProfileResponse.data.data.user.avatar_url);
        console.log('Final profile completion:', finalProfileResponse.data.data.user.profile_completion + '%');

        // Clean up
        if (fs.existsSync(tempImagePath)) {
            fs.unlinkSync(tempImagePath);
            console.log('🧹 Cleaned up test file');
        }

    } catch (error) {
        console.error('❌ Test failed:', error.response?.data || error.message);
    }
}

testAvatarUpload();
