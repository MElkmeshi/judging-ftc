<?php

namespace Database\Seeders;

use App\Models\AwardTemplate;
use App\Models\CriterionTemplate;
use Illuminate\Database\Seeder;

class FtcAwardTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Control Award
        $control = AwardTemplate::create([
            'name' => 'Control Award',
            'code' => 'control',
            'description' => 'Celebrates a team that uses sensors and software to increase the robot\'s functionality on the field.',
            'judging_guidelines' => 'This team demonstrates exceptional use of sensors, autonomous programming, and control systems to enhance robot performance.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Sensors',
            'description' => 'Effective use of sensors for robot awareness and decision making',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Challenge',
            'description' => 'Understanding and addressing the game challenge',
            'weight' => 15.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Functionality and Match Consistency',
            'description' => 'Robot functionality and consistent performance across matches',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Hardware & Software Integration',
            'description' => 'Integration between hardware components and software control',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 4,
        ]);

        // Design Award
        $design = AwardTemplate::create([
            'name' => 'Design Award',
            'code' => 'design',
            'description' => 'Recognizes design elements of the robot that are both functional and aesthetic.',
            'judging_guidelines' => 'This team demonstrates well-designed robot that is both beautiful and functional, with careful attention to form and function.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Simplicity & Easy to Fix',
            'description' => 'Design simplicity and ease of maintenance and repairs',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Effective with Game Plan',
            'description' => 'Design effectiveness in executing the game strategy',
            'weight' => 20.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Process of Design Phases and Functionality of Each Component',
            'description' => 'Documentation of design phases and component functionality',
            'weight' => 10.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Engineering Processes, Lessons to Design, Tradeoff Analysis, Math Equations',
            'description' => 'Engineering processes, design lessons learned, tradeoff analysis and mathematical calculations',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 4,
        ]);

        // Think Award
        $think = AwardTemplate::create([
            'name' => 'Think Award',
            'code' => 'think',
            'description' => 'Presented to the team that best reflects the journey the team took as they experienced the engineering design process.',
            'judging_guidelines' => 'This team shows exceptional documentation of the engineering design process, demonstrates innovation in solving the challenge, and shows clear evidence of iterative design and testing.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'Learn from Mentor, Recruitment of People, Track Goals',
            'description' => 'Learning from mentors, team recruitment, and goal tracking',
            'weight' => 60.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'Portfolio Clear',
            'description' => 'Clarity and quality of engineering portfolio documentation',
            'weight' => 20.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'How They Learned',
            'description' => 'Evidence of learning process and knowledge acquisition',
            'weight' => 15.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'Unique Design',
            'description' => 'Uniqueness and originality of design approach',
            'weight' => 5.00,
            'max_score' => 10,
            'display_order' => 4,
        ]);

        // Innovation Award
        $innovation = AwardTemplate::create([
            'name' => 'Innovation Award',
            'code' => 'innovation',
            'description' => 'Celebrates a team that thinks imaginatively and has the ingenuity, creativity, and inventiveness to make their designs come to life.',
            'judging_guidelines' => 'This team demonstrates exceptional creativity and innovation in their robot design and problem-solving approach.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 4,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $innovation->id,
            'name' => 'Solid Shape',
            'description' => 'Structural design and robot shape effectiveness',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $innovation->id,
            'name' => 'Community',
            'description' => 'Community engagement and impact',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $innovation->id,
            'name' => 'Sustainability',
            'description' => 'Sustainability of design and team practices',
            'weight' => 35.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Motivation Award
        $motivation = AwardTemplate::create([
            'name' => 'Motivation Award',
            'code' => 'motivation',
            'description' => 'Celebrates the team that embraces the culture of FIRST and clearly demonstrates what it means to be a team.',
            'judging_guidelines' => 'This team demonstrates exceptional team spirit, motivation, and commitment to FIRST values.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 5,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $motivation->id,
            'name' => 'Year Plan',
            'description' => 'Long-term planning and season goals',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $motivation->id,
            'name' => 'How They Get Money',
            'description' => 'Fundraising strategies and financial sustainability',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $motivation->id,
            'name' => 'Team Plan & Goals',
            'description' => 'Team planning, goal setting, and achievement tracking',
            'weight' => 20.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $motivation->id,
            'name' => 'Expert Engagement',
            'description' => 'Engagement with experts, mentors, and professionals',
            'weight' => 20.00,
            'max_score' => 10,
            'display_order' => 4,
        ]);

        // Connect Award
        $connect = AwardTemplate::create([
            'name' => 'Connect Award',
            'code' => 'connect',
            'description' => 'Presented to the team that most connects with their local science, technology, engineering and math (STEM) community.',
            'judging_guidelines' => 'This team has a significant and effective connection with their local community, including businesses, educational institutions, and mentors.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 6,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $connect->id,
            'name' => 'Examples',
            'description' => 'Concrete examples of community connections and impact',
            'weight' => 50.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $connect->id,
            'name' => 'Recruitment',
            'description' => 'Team recruitment and member engagement strategies',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $connect->id,
            'name' => 'Outreach Objectives',
            'description' => 'Clear outreach objectives and goals',
            'weight' => 20.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Reach Award
        $reach = AwardTemplate::create([
            'name' => 'Reach Award',
            'code' => 'reach',
            'description' => 'Presented to the team that has the most significant measurable impact on the culture of FIRST.',
            'judging_guidelines' => 'This team demonstrates exceptional reach in promoting FIRST and STEM in their community.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 7,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $reach->id,
            'name' => 'Marketing FIRST',
            'description' => 'Marketing and promotion of FIRST programs',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $reach->id,
            'name' => 'Planning',
            'description' => 'Strategic planning for outreach activities',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $reach->id,
            'name' => 'Progress Tracking',
            'description' => 'Tracking and measuring outreach progress and impact',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Sustain Award
        $sustain = AwardTemplate::create([
            'name' => 'Sustain Award',
            'code' => 'sustain',
            'description' => 'Presented to the team that has developed and implemented a comprehensive plan for sustained team success.',
            'judging_guidelines' => 'This team demonstrates exceptional planning for long-term sustainability and growth.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 8,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $sustain->id,
            'name' => 'Structure & Risk',
            'description' => 'Team structure, organization, and risk management',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $sustain->id,
            'name' => 'Sustainability Planning',
            'description' => 'Long-term sustainability planning and execution',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $sustain->id,
            'name' => 'Resource Management',
            'description' => 'Management of team resources and continuity planning',
            'weight' => 35.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);
    }
}
