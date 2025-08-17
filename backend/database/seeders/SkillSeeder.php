<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Skill;
use Illuminate\Support\Str;

class SkillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $skills = [
            // Programming & Software Development
            ['name_en' => 'JavaScript', 'name_ar' => 'جافا سكريبت', 'category' => 'programming', 'difficulty_level' => 'intermediate', 'description_en' => 'Modern web programming language', 'description_ar' => 'لغة برمجة الويب الحديثة'],
            ['name_en' => 'Python', 'name_ar' => 'بايثون', 'category' => 'programming', 'difficulty_level' => 'beginner', 'description_en' => 'Versatile programming language', 'description_ar' => 'لغة برمجة متعددة الاستخدامات'],
            ['name_en' => 'Java', 'name_ar' => 'جافا', 'category' => 'programming', 'difficulty_level' => 'intermediate', 'description_en' => 'Object-oriented programming language', 'description_ar' => 'لغة برمجة كائنية التوجه'],
            ['name_en' => 'C++', 'name_ar' => 'سي بلس بلس', 'category' => 'programming', 'difficulty_level' => 'advanced', 'description_en' => 'System programming language', 'description_ar' => 'لغة برمجة الأنظمة'],
            ['name_en' => 'React', 'name_ar' => 'ريأكت', 'category' => 'web_development', 'difficulty_level' => 'intermediate', 'description_en' => 'JavaScript library for building UIs', 'description_ar' => 'مكتبة جافا سكريبت لبناء واجهات المستخدم'],
            ['name_en' => 'Vue.js', 'name_ar' => 'فيو جي إس', 'category' => 'web_development', 'difficulty_level' => 'intermediate', 'description_en' => 'Progressive JavaScript framework', 'description_ar' => 'إطار عمل جافا سكريبت تدريجي'],
            ['name_en' => 'Angular', 'name_ar' => 'أنجولار', 'category' => 'web_development', 'difficulty_level' => 'advanced', 'description_en' => 'TypeScript-based web framework', 'description_ar' => 'إطار عمل ويب قائم على تايب سكريبت'],
            ['name_en' => 'Node.js', 'name_ar' => 'نود جي إس', 'category' => 'backend_development', 'difficulty_level' => 'intermediate', 'description_en' => 'JavaScript runtime for servers', 'description_ar' => 'بيئة تشغيل جافا سكريبت للخوادم'],
            ['name_en' => 'Laravel', 'name_ar' => 'لارافيل', 'category' => 'backend_development', 'difficulty_level' => 'intermediate', 'description_en' => 'PHP web application framework', 'description_ar' => 'إطار عمل تطبيقات الويب بي إتش بي'],
            ['name_en' => 'Django', 'name_ar' => 'دجانجو', 'category' => 'backend_development', 'difficulty_level' => 'intermediate', 'description_en' => 'Python web framework', 'description_ar' => 'إطار عمل الويب بايثون'],
            
            // Data Science & AI
            ['name_en' => 'Machine Learning', 'name_ar' => 'تعلم الآلة', 'category' => 'data_science', 'difficulty_level' => 'advanced', 'description_en' => 'AI and predictive modeling', 'description_ar' => 'الذكاء الاصطناعي والنمذجة التنبؤية'],
            ['name_en' => 'Data Analysis', 'name_ar' => 'تحليل البيانات', 'category' => 'data_science', 'difficulty_level' => 'intermediate', 'description_en' => 'Statistical analysis and insights', 'description_ar' => 'التحليل الإحصائي والرؤى'],
            ['name_en' => 'Deep Learning', 'name_ar' => 'التعلم العميق', 'category' => 'data_science', 'difficulty_level' => 'advanced', 'description_en' => 'Neural networks and AI', 'description_ar' => 'الشبكات العصبية والذكاء الاصطناعي'],
            ['name_en' => 'TensorFlow', 'name_ar' => 'تنسورفلو', 'category' => 'data_science', 'difficulty_level' => 'advanced', 'description_en' => 'Machine learning framework', 'description_ar' => 'إطار عمل تعلم الآلة'],
            ['name_en' => 'Pandas', 'name_ar' => 'باندس', 'category' => 'data_science', 'difficulty_level' => 'intermediate', 'description_en' => 'Python data manipulation library', 'description_ar' => 'مكتبة معالجة البيانات بايثون'],
            
            // Design & Multimedia
            ['name_en' => 'UI/UX Design', 'name_ar' => 'تصميم واجهة وتجربة المستخدم', 'category' => 'design', 'difficulty_level' => 'intermediate', 'description_en' => 'User interface and experience design', 'description_ar' => 'تصميم واجهة وتجربة المستخدم'],
            ['name_en' => 'Graphic Design', 'name_ar' => 'التصميم الجرافيكي', 'category' => 'design', 'difficulty_level' => 'beginner', 'description_en' => 'Visual communication design', 'description_ar' => 'تصميم التواصل البصري'],
            ['name_en' => 'Adobe Photoshop', 'name_ar' => 'أدوبي فوتوشوب', 'category' => 'design', 'difficulty_level' => 'intermediate', 'description_en' => 'Image editing and design software', 'description_ar' => 'برنامج تحرير وتصميم الصور'],
            ['name_en' => 'Adobe Illustrator', 'name_ar' => 'أدوبي إليستريتر', 'category' => 'design', 'difficulty_level' => 'intermediate', 'description_en' => 'Vector graphics software', 'description_ar' => 'برنامج الرسوميات المتجهة'],
            ['name_en' => 'Figma', 'name_ar' => 'فيجما', 'category' => 'design', 'difficulty_level' => 'beginner', 'description_en' => 'Collaborative design tool', 'description_ar' => 'أداة التصميم التشاركي'],
            
            // Business & Management
            ['name_en' => 'Digital Marketing', 'name_ar' => 'التسويق الرقمي', 'category' => 'business', 'difficulty_level' => 'beginner', 'description_en' => 'Online marketing strategies', 'description_ar' => 'استراتيجيات التسويق الإلكتروني'],
            ['name_en' => 'Social Media Marketing', 'name_ar' => 'تسويق وسائل التواصل الاجتماعي', 'category' => 'business', 'difficulty_level' => 'beginner', 'description_en' => 'Social platform marketing', 'description_ar' => 'تسويق منصات التواصل الاجتماعي'],
            ['name_en' => 'Project Management', 'name_ar' => 'إدارة المشاريع', 'category' => 'business', 'difficulty_level' => 'intermediate', 'description_en' => 'Planning and executing projects', 'description_ar' => 'تخطيط وتنفيذ المشاريع'],
            ['name_en' => 'Financial Analysis', 'name_ar' => 'التحليل المالي', 'category' => 'business', 'difficulty_level' => 'intermediate', 'description_en' => 'Financial data interpretation', 'description_ar' => 'تفسير البيانات المالية'],
            ['name_en' => 'Business Analysis', 'name_ar' => 'تحليل الأعمال', 'category' => 'business', 'difficulty_level' => 'intermediate', 'description_en' => 'Business process optimization', 'description_ar' => 'تحسين العمليات التجارية'],
            
            // Engineering
            ['name_en' => 'AutoCAD', 'name_ar' => 'أوتوكاد', 'category' => 'engineering', 'difficulty_level' => 'intermediate', 'description_en' => 'Computer-aided design software', 'description_ar' => 'برنامج التصميم بمساعدة الحاسوب'],
            ['name_en' => 'SolidWorks', 'name_ar' => 'سوليد ووركس', 'category' => 'engineering', 'difficulty_level' => 'advanced', 'description_en' => '3D CAD software', 'description_ar' => 'برنامج التصميم ثلاثي الأبعاد'],
            ['name_en' => 'MATLAB', 'name_ar' => 'ماتلاب', 'category' => 'engineering', 'difficulty_level' => 'advanced', 'description_en' => 'Mathematical computing software', 'description_ar' => 'برنامج الحوسبة الرياضية'],
            ['name_en' => 'Circuit Design', 'name_ar' => 'تصميم الدوائر', 'category' => 'engineering', 'difficulty_level' => 'intermediate', 'description_en' => 'Electronic circuit design', 'description_ar' => 'تصميم الدوائر الإلكترونية'],
            
            // Languages
            ['name_en' => 'English Language', 'name_ar' => 'اللغة الإنجليزية', 'category' => 'languages', 'difficulty_level' => 'beginner', 'description_en' => 'English communication skills', 'description_ar' => 'مهارات التواصل باللغة الإنجليزية'],
            ['name_en' => 'Arabic Language', 'name_ar' => 'اللغة العربية', 'category' => 'languages', 'difficulty_level' => 'beginner', 'description_en' => 'Arabic communication skills', 'description_ar' => 'مهارات التواصل باللغة العربية'],
            ['name_en' => 'French Language', 'name_ar' => 'اللغة الفرنسية', 'category' => 'languages', 'difficulty_level' => 'intermediate', 'description_en' => 'French communication skills', 'description_ar' => 'مهارات التواصل باللغة الفرنسية'],
            ['name_en' => 'German Language', 'name_ar' => 'اللغة الألمانية', 'category' => 'languages', 'difficulty_level' => 'intermediate', 'description_en' => 'German communication skills', 'description_ar' => 'مهارات التواصل باللغة الألمانية'],
            
            // Medical & Health
            ['name_en' => 'Clinical Research', 'name_ar' => 'البحث السريري', 'category' => 'medical', 'difficulty_level' => 'advanced', 'description_en' => 'Medical research methodology', 'description_ar' => 'منهجية البحث الطبي'],
            ['name_en' => 'Anatomy', 'name_ar' => 'علم التشريح', 'category' => 'medical', 'difficulty_level' => 'intermediate', 'description_en' => 'Human body structure', 'description_ar' => 'تركيب جسم الإنسان'],
            ['name_en' => 'Pharmacology', 'name_ar' => 'علم الأدوية', 'category' => 'medical', 'difficulty_level' => 'advanced', 'description_en' => 'Drug interactions and effects', 'description_ar' => 'تفاعلات وتأثيرات الأدوية'],
            
            // Academic & Research
            ['name_en' => 'Academic Writing', 'name_ar' => 'الكتابة الأكاديمية', 'category' => 'academic', 'difficulty_level' => 'intermediate', 'description_en' => 'Scholarly writing skills', 'description_ar' => 'مهارات الكتابة العلمية'],
            ['name_en' => 'Research Methodology', 'name_ar' => 'منهجية البحث', 'category' => 'academic', 'difficulty_level' => 'intermediate', 'description_en' => 'Scientific research methods', 'description_ar' => 'طرق البحث العلمي'],
            ['name_en' => 'Statistics', 'name_ar' => 'الإحصاء', 'category' => 'academic', 'difficulty_level' => 'intermediate', 'description_en' => 'Statistical analysis methods', 'description_ar' => 'طرق التحليل الإحصائي'],
            ['name_en' => 'Presentation Skills', 'name_ar' => 'مهارات العرض', 'category' => 'academic', 'difficulty_level' => 'beginner', 'description_en' => 'Public speaking and presentations', 'description_ar' => 'الخطابة والعروض التقديمية'],
            
            // Creative & Arts
            ['name_en' => 'Video Editing', 'name_ar' => 'تحرير الفيديو', 'category' => 'creative', 'difficulty_level' => 'intermediate', 'description_en' => 'Video production and editing', 'description_ar' => 'إنتاج وتحرير الفيديو'],
            ['name_en' => 'Photography', 'name_ar' => 'التصوير', 'category' => 'creative', 'difficulty_level' => 'beginner', 'description_en' => 'Digital photography skills', 'description_ar' => 'مهارات التصوير الرقمي'],
            ['name_en' => 'Music Production', 'name_ar' => 'إنتاج الموسيقى', 'category' => 'creative', 'difficulty_level' => 'advanced', 'description_en' => 'Audio production and mixing', 'description_ar' => 'إنتاج ومزج الصوت'],
            ['name_en' => 'Content Writing', 'name_ar' => 'كتابة المحتوى', 'category' => 'creative', 'difficulty_level' => 'beginner', 'description_en' => 'Creative and marketing writing', 'description_ar' => 'الكتابة الإبداعية والتسويقية'],
        ];

        foreach ($skills as $skillData) {
            $skillData['slug'] = Str::slug($skillData['name_en']);
            $skillData['popularity_score'] = rand(10, 100);
            $skillData['is_verified'] = true;
            $skillData['is_active'] = true;
            
            Skill::create($skillData);
        }
    }
}
