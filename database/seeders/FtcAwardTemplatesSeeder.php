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
        // Inspire Award (Hierarchical - top award)
        $inspire = AwardTemplate::create([
            'name' => 'Inspire Award',
            'code' => 'inspire',
            'description' => 'The Inspire Award is the top award and is presented to the team that embodied the \'challenge\' of FIRST Tech Challenge.',
            'judging_guidelines' => 'This team is a strong ambassador for FIRST programs and a role model FIRST team. They are a gracious competitor and use their experience to inspire other teams and members of their community to adopt the culture of FIRST.',
            'is_ranked' => true,
            'is_hierarchical' => true,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $inspire->id,
            'name' => 'Team Collaboration',
            'description' => 'Works effectively together, values contributions of all members, demonstrates gracious professionalism',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $inspire->id,
            'name' => 'Outreach Impact',
            'description' => 'Promotes FIRST and STEM in their community, inspires others',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $inspire->id,
            'name' => 'Engineering Excellence',
            'description' => 'Demonstrates strong engineering and design process, innovative approach',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $inspire->id,
            'name' => 'Team Sustainability',
            'description' => 'Has sustainable plan for team continuity and growth, mentor involvement',
            'weight' => 20.00,
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
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'Engineering Process Documentation',
            'description' => 'Quality of engineering notebook, documentation of design iterations and testing',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'Innovation and Problem Solving',
            'description' => 'Creative solutions to challenges, unique approaches to the game',
            'weight' => 35.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $think->id,
            'name' => 'Team Reflection and Learning',
            'description' => 'Evidence of learning from failures, adaptation and improvement',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Connect Award
        $connect = AwardTemplate::create([
            'name' => 'Connect Award',
            'code' => 'connect',
            'description' => 'Presented to the team that most connects with their local science, technology, engineering and math (STEM) community.',
            'judging_guidelines' => 'This team has a significant and effective connection with their local community, including businesses, educational institutions, and mentors.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 3,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $connect->id,
            'name' => 'Community Partnerships',
            'description' => 'Strength and breadth of connections with local STEM community, businesses, schools',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $connect->id,
            'name' => 'Outreach Sustainability',
            'description' => 'Long-term impact and sustainability of community connections',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $connect->id,
            'name' => 'Communication Effectiveness',
            'description' => 'Quality of communication with sponsors, partners, and community',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Innovate Award
        $innovate = AwardTemplate::create([
            'name' => 'Innovate Award',
            'code' => 'innovate',
            'description' => 'Celebrates a team that thinks imaginatively and has the ingenuity, creativity, and inventiveness to make their designs come to life.',
            'judging_guidelines' => 'This team demonstrates exceptional creativity and innovation in their robot design and problem-solving approach.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 4,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $innovate->id,
            'name' => 'Creative Problem Solving',
            'description' => 'Originality in approaching the challenge, unique solutions',
            'weight' => 35.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $innovate->id,
            'name' => 'Novel Design Elements',
            'description' => 'Innovative robot mechanisms, creative use of materials and technology',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $innovate->id,
            'name' => 'Implementation Effectiveness',
            'description' => 'How well the innovative ideas were executed and perform',
            'weight' => 25.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Control Award
        $control = AwardTemplate::create([
            'name' => 'Control Award',
            'code' => 'control',
            'description' => 'Celebrates a team that uses sensors and software to increase the robot\'s functionality on the field.',
            'judging_guidelines' => 'This team demonstrates exceptional use of sensors, autonomous programming, and control systems to enhance robot performance.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 5,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Sensor Integration',
            'description' => 'Effective use of sensors for robot awareness and decision making',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Software Quality',
            'description' => 'Code structure, architecture, and implementation quality',
            'weight' => 40.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $control->id,
            'name' => 'Autonomous Performance',
            'description' => 'Effectiveness and reliability of autonomous operation',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Design Award
        $design = AwardTemplate::create([
            'name' => 'Design Award',
            'code' => 'design',
            'description' => 'Recognizes design elements of the robot that are both functional and aesthetic.',
            'judging_guidelines' => 'This team demonstrates well-designed robot that is both beautiful and functional, with careful attention to form and function.',
            'is_ranked' => true,
            'is_hierarchical' => false,
            'display_order' => 6,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Design Aesthetics',
            'description' => 'Visual appeal, branding, and overall appearance of the robot',
            'weight' => 20.00,
            'max_score' => 10,
            'display_order' => 1,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Functional Efficiency',
            'description' => 'How well the design serves its purpose, optimization of mechanisms',
            'weight' => 50.00,
            'max_score' => 10,
            'display_order' => 2,
        ]);

        CriterionTemplate::create([
            'award_template_id' => $design->id,
            'name' => 'Build Quality',
            'description' => 'Craftsmanship, durability, and attention to detail',
            'weight' => 30.00,
            'max_score' => 10,
            'display_order' => 3,
        ]);

        // Judges' Choice Award (non-ranked, optional)
        AwardTemplate::create([
            'name' => "Judges' Choice Award",
            'code' => 'judges_choice',
            'description' => 'An optional award that may be presented by the judges to recognize a team for an exceptional achievement or unique circumstance that does not fit into other award categories.',
            'judging_guidelines' => 'This award is at the discretion of the judging panel to recognize something truly special about a team.',
            'is_ranked' => false,
            'is_hierarchical' => false,
            'display_order' => 99,
        ]);

        // Note: Judges' Choice has no predefined criteria - judges use their discretion
    }
}
