const axios = require('axios');
const FormData = require('form-data');
const fs = require('fs');
const path = require('path');

// Test credentials (update these based on your seeded data)
const testUser = {
    email: 'ahmed.hassan@skillswap.eg',
    password: 'password123'
};

const API_BASE = 'http://localhost:8000/api';

async function testProfileUpdate() {
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
        console.log('Current user:', {
            id: profileResponse.data.data.user.id,
            name: profileResponse.data.data.user.first_name + ' ' + profileResponse.data.data.user.last_name,
            email: profileResponse.data.data.user.email,
            avatar: profileResponse.data.data.user.avatar
        });

        // Test profile update via AuthController
        console.log('\n🔄 Testing profile update via AuthController...');
        const updateData = {
            first_name: 'Updated',
            last_name: 'Name',
            bio_en: 'This is my updated bio from test script'
        };

        const updateResponse = await axios.put(`${API_BASE}/auth/profile`, updateData, {
            headers: authHeaders
        });
        console.log('✅ Profile update response:', {
            status: updateResponse.data.status,
            message: updateResponse.data.message,
            updated_name: updateResponse.data.data.user.first_name + ' ' + updateResponse.data.data.user.last_name,
            updated_bio: updateResponse.data.data.user.bio_en
        });

        // Create a dummy image file for avatar test
        console.log('\n📸 Creating dummy image for avatar test...');
        const dummyImageBuffer = Buffer.from('dummy-image-data');
        const tempImagePath = path.join(__dirname, 'temp-avatar.jpg');
        fs.writeFileSync(tempImagePath, dummyImageBuffer);

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
            console.log('✅ Avatar upload response:', {
                status: avatarResponse.data.status,
                message: avatarResponse.data.message,
                avatar_url: avatarResponse.data.data.avatar_url,
                user_avatar: avatarResponse.data.data.user ? avatarResponse.data.data.user.avatar : 'not included'
            });
        } catch (avatarError) {
            console.log('❌ Avatar upload failed:', avatarError.response?.data || avatarError.message);
        }

        // Verify changes by getting profile again
        console.log('\n🔍 Verifying changes...');
        const finalProfileResponse = await axios.get(`${API_BASE}/auth/profile`, {
            headers: authHeaders
        });
        console.log('Final profile:', {
            name: finalProfileResponse.data.data.user.first_name + ' ' + finalProfileResponse.data.data.user.last_name,
            bio: finalProfileResponse.data.data.user.bio_en,
            avatar: finalProfileResponse.data.data.user.avatar
        });

        // Clean up
        if (fs.existsSync(tempImagePath)) {
            fs.unlinkSync(tempImagePath);
        }

    } catch (error) {
        console.error('❌ Test failed:', error.response?.data || error.message);
    }
}

testProfileUpdate();
