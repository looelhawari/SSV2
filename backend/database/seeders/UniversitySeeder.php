<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\University;
use App\Models\Faculty;
use App\Models\Major;

class UniversitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Major Egyptian Universities
        $universities = [
            [
                'name_en' => 'Cairo University',
                'name_ar' => 'جامعة القاهرة',
                'code' => 'CU',
                'type' => 'public',
                'city' => 'Cairo',
                'description_en' => 'Egypt\'s premier university established in 1908',
                'description_ar' => 'الجامعة الرائدة في مصر تأسست عام 1908',
                'website' => 'https://cu.edu.eg',
                'faculties' => [
                    [
                        'name_en' => 'Faculty of Engineering',
                        'name_ar' => 'كلية الهندسة',
                        'code' => 'ENG',
                        'majors' => [
                            ['name_en' => 'Computer Science', 'name_ar' => 'علوم الحاسوب', 'code' => 'CS'],
                            ['name_en' => 'Electrical Engineering', 'name_ar' => 'هندسة كهربائية', 'code' => 'EE'],
                            ['name_en' => 'Mechanical Engineering', 'name_ar' => 'هندسة ميكانيكية', 'code' => 'ME'],
                            ['name_en' => 'Civil Engineering', 'name_ar' => 'هندسة مدنية', 'code' => 'CE'],
                        ]
                    ],
                    [
                        'name_en' => 'Faculty of Medicine',
                        'name_ar' => 'كلية الطب',
                        'code' => 'MED',
                        'majors' => [
                            ['name_en' => 'Medicine', 'name_ar' => 'الطب', 'code' => 'MD'],
                            ['name_en' => 'Pharmacy', 'name_ar' => 'الصيدلة', 'code' => 'PHARM'],
                        ]
                    ],
                    [
                        'name_en' => 'Faculty of Economics and Political Science',
                        'name_ar' => 'كلية الاقتصاد والعلوم السياسية',
                        'code' => 'ECON',
                        'majors' => [
                            ['name_en' => 'Economics', 'name_ar' => 'الاقتصاد', 'code' => 'ECON'],
                            ['name_en' => 'Political Science', 'name_ar' => 'العلوم السياسية', 'code' => 'POLI'],
                        ]
                    ]
                ]
            ],
            [
                'name_en' => 'American University in Cairo',
                'name_ar' => 'الجامعة الأمريكية بالقاهرة',
                'code' => 'AUC',
                'type' => 'private',
                'city' => 'Cairo',
                'description_en' => 'Leading American-style university in the Middle East',
                'description_ar' => 'جامعة رائدة على النمط الأمريكي في الشرق الأوسط',
                'website' => 'https://aucegypt.edu',
                'faculties' => [
                    [
                        'name_en' => 'School of Sciences and Engineering',
                        'name_ar' => 'كلية العلوم والهندسة',
                        'code' => 'SSE',
                        'majors' => [
                            ['name_en' => 'Computer Science', 'name_ar' => 'علوم الحاسوب', 'code' => 'CS'],
                            ['name_en' => 'Computer Engineering', 'name_ar' => 'هندسة الحاسوب', 'code' => 'CENG'],
                        ]
                    ],
                    [
                        'name_en' => 'School of Business',
                        'name_ar' => 'كلية إدارة الأعمال',
                        'code' => 'BUS',
                        'majors' => [
                            ['name_en' => 'Business Administration', 'name_ar' => 'إدارة الأعمال', 'code' => 'BBA'],
                            ['name_en' => 'Finance', 'name_ar' => 'التمويل', 'code' => 'FIN'],
                        ]
                    ]
                ]
            ],
            [
                'name_en' => 'Alexandria University',
                'name_ar' => 'جامعة الإسكندرية',
                'code' => 'ALEX',
                'type' => 'public',
                'city' => 'Alexandria',
                'description_en' => 'Second oldest university in Egypt',
                'description_ar' => 'ثاني أقدم جامعة في مصر',
                'website' => 'https://alexu.edu.eg',
                'faculties' => [
                    [
                        'name_en' => 'Faculty of Engineering',
                        'name_ar' => 'كلية الهندسة',
                        'code' => 'ENG',
                        'majors' => [
                            ['name_en' => 'Computer Engineering', 'name_ar' => 'هندسة الحاسوب', 'code' => 'CENG'],
                            ['name_en' => 'Electronics Engineering', 'name_ar' => 'هندسة الإلكترونيات', 'code' => 'ELEC'],
                        ]
                    ]
                ]
            ],
            [
                'name_en' => 'Ain Shams University',
                'name_ar' => 'جامعة عين شمس',
                'code' => 'ASU',
                'type' => 'public',
                'city' => 'Cairo',
                'description_en' => 'One of Egypt\'s largest universities',
                'description_ar' => 'واحدة من أكبر الجامعات في مصر',
                'website' => 'https://asu.edu.eg',
                'faculties' => [
                    [
                        'name_en' => 'Faculty of Computers and Information',
                        'name_ar' => 'كلية الحاسبات والمعلومات',
                        'code' => 'FCI',
                        'majors' => [
                            ['name_en' => 'Computer Science', 'name_ar' => 'علوم الحاسوب', 'code' => 'CS'],
                            ['name_en' => 'Information Systems', 'name_ar' => 'نظم المعلومات', 'code' => 'IS'],
                        ]
                    ]
                ]
            ]
        ];

        foreach ($universities as $universityData) {
            $faculties = $universityData['faculties'];
            unset($universityData['faculties']);

            $university = University::create($universityData);

            foreach ($faculties as $facultyData) {
                $majors = $facultyData['majors'];
                unset($facultyData['majors']);
                
                $facultyData['university_id'] = $university->id;
                $faculty = Faculty::create($facultyData);

                foreach ($majors as $majorData) {
                    $majorData['faculty_id'] = $faculty->id;
                    Major::create($majorData);
                }
            }
        }
    }
}
