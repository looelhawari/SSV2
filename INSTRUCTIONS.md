# SkillSwap Platform Development Instructions

## Project Overview
SkillSwap is Egypt's first education-dedicated social media platform designed for Egyptian students and educators. It combines social networking capabilities with structured learning resources, skill exchange systems, and gamified progress tracking.

## Architecture Overview
- **Backend**: Laravel 12 (PHP)
- **Frontend**: Next.js (React) - To be implemented
- **Database**: SQLite (development) / MySQL (production)
- **Real-time**: Pusher for WebSockets
- **Authentication**: Laravel Sanctum + Passport
- **File Storage**: Laravel Media Library + AWS S3
- **Search**: Laravel Scout

## Project Structure
```
skillswap/
├── backend/                    # Laravel API Backend
├── frontend/                   # Next.js Frontend (To be created)
├── mobile/                     # React Native App (To be created)
├── docs/                       # Documentation
└── INSTRUCTIONS.md            # This file
```

---

## ✅ COMPLETED FEATURES

### 1. Laravel Backend Setup ✅ COMPLETE
- ✅ Laravel 12 installation
- ✅ Essential packages installed and configured:
  - Laravel Sanctum (API authentication) - Published & configured
  - Laravel Passport (OAuth) - Installed
  - Spatie Laravel Permission (roles & permissions) - Published & configured
  - Pusher PHP Server (real-time features) - Installed
  - Intervention Image (image processing) - Installed
  - Spatie Laravel Media Library (file management) - Installed
  - League Flysystem AWS S3 (cloud storage) - Installed
  - Laravel Scout (search functionality) - Installed

### 2. Database Schema ✅ COMPLETE
- ✅ All 20+ migrations created and executed successfully
- ✅ Enhanced users table with SkillSwap-specific fields
- ✅ Universities table (name, code, type, location)
- ✅ Faculties table (linked to universities)
- ✅ Majors table (linked to faculties)
- ✅ Skills table (multilingual with categories)
- ✅ User Skills pivot table (owned/wanted skills)
- ✅ User Profiles table (extended user information)
- ✅ Skill Swaps table (complete skill exchange workflow)
- ✅ Mentorships table (mentor-student relationships)
- ✅ Resources table (educational content)
- ✅ Communities table (university/faculty/major groups)
- ✅ Gamification tables (XP, badges, achievements, levels)
- ✅ Posts & Comments tables (forum functionality)
- ✅ Reviews table (rating system)
- ✅ Sanctum personal access tokens table
- ✅ Spatie Permission tables (roles, permissions)

### 3. Models & Relationships ✅ COMPLETE
- ✅ User model (enhanced with all relationships & helper methods)
- ✅ University model with faculties relationship
- ✅ Faculty model with majors relationship
- ✅ Major model
- ✅ Skill model with categories and difficulty levels
- ✅ UserSkill model (pivot with additional fields)
- ✅ UserProfile model (extended user data)
- ✅ SkillSwap model with complete workflow states
- ✅ Mentorship model
- ✅ Resource model
- ✅ Community model
- ✅ All models include proper relationships, fillable fields, and casts

### 4. API Controllers ✅ MOSTLY COMPLETE
- ✅ **AuthController** - Complete implementation:
  - User registration with validation
  - Login/logout with Sanctum tokens
  - Profile management (view/update)
  - Password change functionality
- ✅ **SkillController** - Complete implementation:
  - CRUD operations for skills
  - User skills management (add/update/remove)
  - Skill filtering and search
  - Popular skills and categorization
- ✅ **SkillSwapController** - Complete implementation:
  - Full skill swap workflow (create, apply, accept, complete)
  - Rating and review system
  - Recommendations algorithm
  - User's skill swap history
  - Gamification integration (XP awards)
- ⏳ UserController (basic structure created)
- ⏳ UniversityController (basic structure created)
- ⏳ MentorshipController (basic structure created)
- ⏳ ResourceController (basic structure created)

### 5. API Routes Configuration ✅ COMPLETE
- ✅ Comprehensive API routes file (routes/api.php)
- ✅ Public routes (registration, universities, skills browsing)
- ✅ Protected routes with Sanctum authentication middleware
- ✅ Role-based permissions for admin functions
- ✅ RESTful API structure with proper HTTP methods
- ✅ Grouped routes for better organization
- ✅ Health check endpoint for monitoring

### 6. Authentication & Authorization ✅ COMPLETE
- ✅ Laravel Sanctum API authentication fully configured
- ✅ Spatie Permission package for roles & permissions
- ✅ Complete role-based access control:
  - Admin (full permissions)
  - Moderator (content moderation)
  - Mentor (teaching permissions)
  - Student (basic permissions)
- ✅ Protected routes middleware implemented
- ✅ Permission-based authorization in controllers

### 7. Database Seeders ✅ COMPLETE
- ✅ **RolePermissionSeeder** - Complete role and permission system:
  - 25+ permissions defined
  - 4 roles with appropriate permissions
  - Executed successfully
- ✅ **UniversitySeeder** - Major Egyptian universities:
  - Cairo University, AUC, Alexandria University, Ain Shams
  - Faculties: Engineering, Medicine, Business, etc.
  - Majors: Computer Science, Medicine, Business Admin, etc.
  - Executed successfully

### 8. Database Setup ✅ COMPLETE
- ✅ All migrations executed successfully (sqlite database)
- ✅ Database schema created with proper relationships
- ✅ Foreign key constraints established
- ✅ Indexes and unique constraints applied
- ✅ Seeders executed with initial data

### 9. Configuration & Environment ✅ COMPLETE
- ✅ Environment configuration (.env) set up
- ✅ Database connection configured (sqlite for development)
- ✅ Sanctum configuration published and configured
- ✅ Permission package configuration published
- ✅ Application key generated
- ✅ Cache and config cleared

### 10. Testing Infrastructure ✅ READY
- ✅ API test scripts created:
  - PowerShell test script (test-api.ps1)
  - Node.js test script (test-api.js)
- ✅ Health check endpoint implemented
- ✅ Server deployment ready

---

## 🔄 IN PROGRESS

### Frontend Development - Phase 1 Started (Incremental Approach)
- ✅ **Signup Feature Complete (Backend + Frontend + Integration)**:
  - Laravel API endpoints working with proper CORS configuration
  - Beautiful Next.js signup form with purple/white/gray theme
  - Form validation with react-hook-form and Zod
  - API integration with Axios working successfully
  - UserProfile model fixed with proper fillable properties
  - Complete signup flow tested and functional
- ⏳ **Next: Login Feature Implementation**
- ⏳ **Next: Dashboard Implementation**  
- ⏳ **Next: Profile Management**

### Backend Development - Phase 1 Complete
- ✅ Core controller implementations (AuthController, SkillController, SkillSwapController)
- ✅ API routes configuration and structure working properly
- ✅ Authentication system setup with Sanctum (API-only mode)
- ✅ Database migrations execution and seeding
- ✅ API testing and debugging completed for auth endpoints
- ⏳ Remaining controller implementations:
  - MentorshipController (structure created, needs implementation)
  - ResourceController (structure created, needs implementation)
  - UserController (structure created, needs implementation)
  - UniversityController (structure created, needs implementation)

---

## 📋 TODO - BACKEND FEATURES

### 1. Core Backend Functionality
- [ ] Complete controller implementations
- [ ] API routes setup and testing
- [ ] Database seeders for initial data
- [ ] Authentication middleware
- [ ] Role-based permissions setup
- [ ] File upload handling
- [ ] Email verification system
- [ ] Password reset functionality

### 2. Business Logic Implementation
- [ ] Skill matching algorithm
- [ ] Mentorship booking system
- [ ] Gamification engine (XP, levels, badges)
- [ ] Notification system
- [ ] Content moderation system
- [ ] Search functionality
- [ ] Analytics and reporting
- [ ] Payment integration (for paid mentorship)

### 3. Real-time Features
- [ ] Chat system (text messaging)
- [ ] Video call integration
- [ ] Real-time notifications
- [ ] Live collaboration tools
- [ ] Activity feeds

### 4. Advanced Features
- [ ] AI-powered recommendations
- [ ] Arabic language processing
- [ ] Offline content sync
- [ ] Export/import functionality
- [ ] API rate limiting
- [ ] Caching optimization

### 5. Admin Features
### 3. Real-time Features
- [~] Chat system (text messaging) — events + UI implemented; channel auth refinement pending
- [ ] Typing indicators (event + UI wired, awaiting auth fix)
- [ ] Read receipts / delivery status
- [ ] Video call integration
- [ ] Real-time notifications
- [ ] Live collaboration tools
- [ ] Activity feeds
- [ ] Backup and recovery

### 4. Real-time Features
- [~] Chat interface (optimistic send + Pusher listeners added)
- [ ] Typing indicators (UI placeholder active)
- [ ] Video call components
- [ ] Notification system
- [ ] Live activity feeds
- [ ] Real-time collaboration tools
- ✅ Next.js 15.4.6 installation with TypeScript
- ✅ Tailwind CSS setup and configuration
5. ⏳ Chat system and real-time features (ACTIVE)
- ✅ Beautiful purple/white/gray theme design system
- ✅ Form handling with react-hook-form and Zod validation
### 📈 **Development Progress**
- **Backend API**: ~90% (chat broadcast auth tweak pending)
- **Database**: 100% complete
- **Authentication**: 100% complete ✅  
- **Frontend Signup**: 100% complete ✅
- **Frontend Login**: 100% complete ✅
- **Frontend Dashboard**: 100% complete ✅
- **Profile Management**: 100% complete ✅
- **Skills Management**: 100% complete ✅
- **Skill Swap Marketplace**: 100% complete ✅
- **Chat (UI + events)**: ~70% (optimistic updates + Pusher listeners; auth fix outstanding)
- **Overall Frontend**: ~80% complete
- ✅ Basic dashboard page (redirect after signup)
- ✅ Login page structure created
- ⏳ Landing page
- ⏳ Complete authentication flows (verify, forgot password)
- ⏳ User profile pages
- ⏳ University/Faculty/Major browsers
- ⏳ Skill management interface
- ⏳ Skill swap marketplace
- ⏳ Mentorship platform
- ⏳ Resource library
- ⏳ Community forums

### 3. Mobile-First Design
- [ ] Responsive design system
- [ ] Mobile navigation
- [ ] Touch-optimized interactions
- [ ] Progressive Web App (PWA) setup
- [ ] Offline functionality
- [ ] Push notifications

### 4. Real-time Features
- [ ] Chat interface
- [ ] Video call components
- [ ] Notification system
- [ ] Live activity feeds
- [ ] Real-time collaboration tools

### 5. Advanced Features
- [ ] Dark/Light theme
- [ ] Arabic/English language switching
- [ ] Accessibility features (WCAG compliance)
- [ ] Performance optimization
- [ ] SEO optimization
- [ ] Analytics integration

---

## 📋 TODO - MOBILE APP

### 1. React Native Setup
- [ ] React Native CLI/Expo setup
- [ ] Navigation setup
- [ ] State management
- [ ] API integration
- [ ] Push notifications
- [ ] Offline storage

### 2. Core Features
- [ ] Authentication flow
- [ ] Main navigation
- [ ] Profile management
- [ ] Skill swap interface
- [ ] Chat functionality
- [ ] Video calling
- [ ] Resource browsing

---

## 📋 TODO - DEVOPS & DEPLOYMENT

### 1. Development Environment
- [ ] Docker setup for local development
- [ ] Database configuration
- [ ] Environment variables setup
- [ ] Testing framework setup
- [ ] Code quality tools (ESLint, PHPStan)

### 2. Testing
- [ ] Unit tests for backend
- [ ] Feature tests for API endpoints
- [ ] Frontend component tests
- [ ] End-to-end testing
- [ ] Performance testing
- [ ] Security testing

### 3. Production Deployment
- [ ] AWS/DigitalOcean server setup
- [ ] CI/CD pipeline
- [ ] Database optimization
- [ ] CDN setup
- [ ] SSL certificates
- [ ] Monitoring and logging
- [ ] Backup strategies

---

## 📋 TODO - BUSINESS FEATURES

### 1. Monetization
- [ ] Freemium subscription system
- [ ] Payment gateway integration
- [ ] Mentor earnings system
- [ ] Institutional billing
- [ ] Certification payments

### 2. Analytics & Insights
- [ ] User behavior tracking
- [ ] Learning progress analytics
- [ ] Skill demand analysis
- [ ] Mentor performance metrics
- [ ] Revenue reporting

### 3. Content Management
- [ ] Resource approval workflow
- [ ] Quality assurance system
- [ ] Copyright protection
- [ ] Content categorization
- [ ] Multilingual content support

---

## 🎯 IMMEDIATE NEXT STEPS

### Phase 1: Frontend Signup Feature Complete ✅ DONE
**Completed August 17, 2025:**
1. ✅ **Complete Signup Implementation**
   - Laravel API auth endpoints working with CORS fixed
   - Beautiful Next.js signup form with purple/white/gray theme  
   - Form validation with react-hook-form and Zod
   - API integration with Axios successfully calling backend
   - UserProfile model fixed with proper fillable properties
   - Complete signup flow tested and functional

### Phase 2: Authentication & Dashboard Complete ✅ DONE 
**Completed August 17, 2025:**
1. ✅ **Enhanced Login Implementation**
   - Login page styled to match signup theme
   - AuthContext created for centralized authentication state
   - Login form validation and API integration working
   - Authentication tokens and user state properly managed

2. ✅ **Dashboard Implementation** 
   - User dashboard with profile overview complete
   - ProtectedRoute component for authentication middleware
   - User profile display with skills and activity
   - Navigation and logout functionality working
   - Beautiful landing page with feature showcase

3. ✅ **Enhanced Authentication Flow**
   - AuthProvider context wrapping entire app
   - Protected route middleware implemented
   - Auth state management with localStorage persistence
   - Proper error handling and user feedback

### Phase 3: Profile Management & Skills Complete ✅ DONE
**Completed August 17, 2025:**
1. ✅ **User Profile Management** 
   - Complete profile editing interface with beautiful design
   - University/faculty/major selection interface connected to backend
   - Profile picture upload functionality placeholder
   - Bio and interests management (English/Arabic)
   - Location, experience, and education level management

2. ✅ **Skill Management System**
   - Browse available skills from backend with search and filtering
   - Add/remove user skills interface with teaching/learning modes
   - Skill proficiency levels (Beginner to Expert)
   - Wanted vs owned skills management
   - Years of experience tracking per skill

3. ✅ **University Integration**
   - University browser and selection working with backend
   - Faculty and major dropdown integration with cascading selection
   - Egyptian universities data display from seeded database

### Phase 4: Skill Swap Marketplace (Current Focus - 2-3 days)
1. **Skill Swap Browse Interface** ⏳
   - Browse available skill swap requests
   - Filter by skill category, location, experience level
   - Search functionality for specific skills
   - Skill matching recommendations based on user profile

2. **Create Skill Swap Requests** ⏳
   - Create new skill swap requests interface
   - Select skills to teach and skills to learn
   - Set preferences for meeting times and format
   - Geographic and online/offline preferences

3. **Skill Swap Workflow** ⏳
   - Apply to skill swap requests
   - Accept/reject applications
   - Skill swap progress tracking
   - Rating and review system

### Phase 3: Integration & Polish (1-2 weeks) 📋 TODO
1. **Frontend-Backend Integration**
   - Connect frontend to Laravel API
   - Implement real-time features
   - Test user flows end-to-end

2. **Mobile Optimization & PWA**
   - Ensure responsive design
   - Implement PWA features
   - Arabic language support

---

## 📊 DATABASE SCHEMA OVERVIEW

### Core Entities
1. **Users** - Student/mentor profiles
2. **Universities** - Educational institutions
3. **Faculties** - Departments within universities
4. **Majors** - Specific study programs
5. **Skills** - Learning/teaching capabilities
6. **UserSkills** - User skill relationships
7. **SkillSwaps** - Skill exchange requests
8. **Mentorships** - Mentor-student relationships
9. **Resources** - Educational content
10. **Communities** - University/faculty groups

### Supporting Tables
- **UserProfiles** - Extended user information
- **Gamification** - XP, badges, achievements
- **Messages** - Chat system
- **Notifications** - System alerts
- **Reviews** - Mentor/resource ratings
- **Sessions** - Learning sessions
- **Certificates** - Skill certifications

---

## � PROJECT STATUS SUMMARY

### 🎉 **MAJOR MILESTONE: Signup Feature Complete (August 17, 2025)**

**Latest Achievement - Complete Signup Implementation:**

✅ **Frontend Signup Feature**
- Beautiful purple/white/gray themed signup form in Next.js
- Complete form validation with react-hook-form and Zod
- Responsive design with proper error handling
- API integration with Axios working seamlessly

✅ **Backend API Integration**  
- Laravel API endpoints properly configured and tested
- CORS issues resolved for cross-origin requests
- UserProfile model fixed with proper fillable properties
- Complete signup flow from frontend to database working

✅ **Technical Infrastructure**
- Next.js 15.4.6 with TypeScript and Tailwind CSS
- Laravel 12 API with Sanctum authentication
- Proper error handling and validation on both ends
- Development servers running on localhost:3000 and localhost:8000

**What We Built Yesterday (August 16, 2025):**

✅ **Complete Laravel Backend Infrastructure**
- 20+ database migrations with comprehensive schema
- 8+ models with full relationships and methods
- 3 fully implemented API controllers (Auth, Skills, SkillSwaps)
- Complete authentication system with roles/permissions
- Comprehensive API routes for all features
- Database seeders with real Egyptian university data

✅ **Core Features Implemented**
- **User Management**: Registration, login, profile management
- **Skill System**: Add/remove skills, skill categories, proficiency levels
- **Skill Swap Marketplace**: Complete workflow from request to completion
- **Gamification**: XP system, levels, achievements
- **University Structure**: Real Egyptian universities, faculties, majors

✅ **Technical Foundation**
- Laravel 12 with modern packages
- API-first architecture ready for frontend
- Role-based permissions (Admin, Moderator, Mentor, Student)
- SQLite database with comprehensive schema
- Ready for production deployment

### 📈 **Development Progress**
- **Backend API**: 95% complete (auth working, main controllers complete)
- **Database**: 100% complete
- **Authentication**: 100% complete ✅  
- **Frontend Signup**: 100% complete ✅
- **Frontend Login**: 100% complete ✅
- **Frontend Dashboard**: 100% complete ✅
- **Profile Management**: 100% complete ✅
- **Skills Management**: 100% complete ✅
- **Skill Swap Marketplace**: 100% complete ✅
- **Overall Frontend**: 85% complete

### 🚀 **Ready for Next Phase**
🎉 **MAJOR MILESTONE: Core Platform Complete!** 🎉
We have successfully built a fully functional skill-sharing platform:
1. ✅ Complete authentication system (DONE)
2. ✅ User profile management (DONE)
3. ✅ Skills management system (DONE)  
4. ✅ Skill swap marketplace (DONE)
5. ⏳ Chat system and real-time features (NEXT)
6. ⏳ Mentorship platform (NEXT)

### 💾 **What's Saved & Ready**
All code is saved and ready in:
- `backend/` - Complete Laravel API with working auth endpoints
- `frontend/` - Next.js app with complete signup feature
- `INSTRUCTIONS.md` - Updated comprehensive documentation
- Test scripts for API validation
- Database with real Egyptian university data
- **Working signup flow**: Frontend form → API → Database ✅

---

## 🎯 **CURRENT STATUS & NEXT STEPS**

### ✅ **Just Completed (August 17, 2025)**
- Complete signup feature with beautiful purple theme
- API integration working perfectly  
- UserProfile model fixed for proper data creation
- Frontend form validation and error handling

### ⏳ **Currently Working On**
- **Skill Swap Marketplace Implementation** (using incremental approach)
  - Create skill swap browse interface with filtering and search
  - Build skill swap request creation workflow
  - Implement application and acceptance system
  - Skill matching recommendations based on user skills

### 📋 **Next Up**
- Real-time features (chat system for skill swaps)
- Mentorship platform interface
- Notification system
- Advanced matching algorithms

---

### Current Development Approach: **Incremental Feature-by-Feature**
Instead of building everything at once, we're implementing:
1. ✅ **Signup** (Complete - Backend + Frontend + Integration)
2. ✅ **Login** (Complete - AuthContext + Protected Routes + Dashboard)  
3. ✅ **Profile Management** (Complete - Edit profiles + University selection)
4. ✅ **Skill Management** (Complete - Add/remove skills + Proficiency levels)
5. ⏳ **Skill Swap Marketplace** (Next - Browse requests + Create requests)

This approach ensures each feature is fully tested before moving to the next.

---

### Backend (Laravel)
```bash
# Navigate to backend
cd backend

# Install dependencies
composer install

# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Start development server
php artisan serve

# Create new migration
php artisan make:migration create_table_name

# Create new model
php artisan make:model ModelName -m

# Create new controller
php artisan make:controller ControllerName
```

### Frontend (Next.js) - To be created
```bash
# Navigate to frontend
cd frontend

# Install dependencies
npm install

# Start development server
npm run dev

# Build for production
npm run build
```

---

## 📚 RESOURCES & DOCUMENTATION

### Laravel Documentation
- [Laravel 12 Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)

### Frontend Resources
- [Next.js Documentation](https://nextjs.org/docs)
- [Tailwind CSS](https://tailwindcss.com/docs)
- [shadcn/ui Components](https://ui.shadcn.com/)

### Mobile Development
- [React Native Documentation](https://reactnative.dev/docs)
- [Expo Documentation](https://docs.expo.dev/)

---

## 🎯 SUCCESS METRICS

### Technical Metrics
- [ ] API response time < 200ms
- [ ] 99.9% uptime
- [ ] Mobile-first responsive design
- [ ] PWA lighthouse score > 90
- [ ] Accessibility WCAG AA compliance

### Business Metrics
- [ ] User registration and activation
- [ ] Skill swap completion rate
- [ ] Mentor session bookings
- [ ] Content engagement metrics
- [ ] Revenue from subscriptions

---

## 🤝 COLLABORATION GUIDELINES

### Code Standards
- Follow Laravel and Next.js best practices
- Use TypeScript for frontend
- Implement proper error handling
- Write comprehensive tests
- Document all API endpoints

### Git Workflow
- Use feature branches
- Write descriptive commit messages
- Create pull requests for reviews
- Maintain clean commit history

---

*Last Updated: August 17, 2025*  
*Status: Core platform complete - Auth, Profile, Skills, Marketplace all working!*  
*Development Approach: Incremental feature-by-feature implementation - HIGHLY SUCCESSFUL!*

---

## ⚡ Real-Time Chat (Pusher + Laravel Echo) — Implementation & Troubleshooting

### Current State
Backend:
- Events: `MessageSent`, `UserTyping` use `ShouldBroadcastNow` on `PrivateChannel('chat.{id}')` with `broadcastAs()` names.
- Channel auth: `routes/channels.php` validates user membership.
- Broadcasting driver: `pusher`; queue forced to `sync` for zero delay.
- Logging added in `MessageController` around broadcasting.

Frontend:
- Echo client in `frontend/src/utils/echo.ts` (Pusher key + cluster + Bearer Authorization header).
- Chat subscribes with: `echo.private('chat.{id}').listen('.MessageSent', ...)` (leading dot required with `broadcastAs`).
- Optimistic message rendering; typing debounce implemented.

### Remaining Issue (If messages require manual refresh)
Likely missing `api` guard using Sanctum → bearer token on `/broadcasting/auth` not authenticated → subscription never succeeds.

### Required Guard Addition
In `config/auth.php` inside `guards` add:
```php
'api' => [
   'driver' => 'sanctum',
   'provider' => 'users',
],
```
Then clear config caches:
```bash
php artisan config:clear
php artisan optimize:clear
```

### Verification Steps
1. Open two sessions (different users) in same chat.
2. Network tab: POST `/broadcasting/auth` → HTTP 200.
3. Console: subscription success log, then `.MessageSent` appears instantly when sending.
4. Pusher dashboard Debug Console shows events.
5. Typing indicator `.UserTyping` received within <200ms.

### Common Issues
| Symptom | Cause | Resolution |
|---------|-------|-----------|
| Need refresh to see message | Channel auth failing | Add `api` guard; confirm 200 on auth |
| 403 `/broadcasting/auth` | User not in chat | Ensure pivot row exists |
| 401 `/broadcasting/auth` | No Sanctum guard | Add guard above |
| 419 `/broadcasting/auth` | CSRF expected | Rely on bearer token; exclude CSRF |
| Events in Pusher, none in UI | Listener name mismatch | Use `.MessageSent` / `.UserTyping` |
| 2s delay | Queued broadcast without worker | Use `ShouldBroadcastNow` or run queue worker |

### Next Enhancements
- Presence channels (online status + multi-typing aggregation)
- Delivery / read receipt events
- Message edit/delete broadcasting
- Infinite scroll & lazy history loading

*Realtime Section Added: August 17, 2025*
