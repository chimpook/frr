<?php

namespace App\DataFixtures;

use App\Entity\Finding;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FindingFixtures extends Fixture
{
    private array $sampleFindings = [
        [
            'location' => 'Building A, Floor 1 - Main Lobby',
            'risk_range' => 'High',
            'comment' => 'Fire extinguisher expired on 2024-06-15. Unit shows visible corrosion and pressure gauge indicates low pressure.',
            'recommendations' => 'Replace fire extinguisher immediately. Schedule monthly inspection routine.',
            'resolved' => false,
        ],
        [
            'location' => 'Building A, Floor 2 - Server Room',
            'risk_range' => 'High',
            'comment' => 'Smoke detection system offline. Last maintenance was 18 months ago. Multiple cable bundles blocking ventilation.',
            'recommendations' => 'Restore smoke detection system within 24 hours. Clear cable obstructions and implement cable management solution.',
            'resolved' => false,
        ],
        [
            'location' => 'Building A, Floor 3 - Kitchen Area',
            'risk_range' => 'Medium',
            'comment' => 'Grease buildup observed in exhaust hood. Fire suppression system inspection overdue by 3 months.',
            'recommendations' => 'Schedule professional hood cleaning. Update fire suppression system inspection.',
            'resolved' => false,
        ],
        [
            'location' => 'Building B, Ground Floor - Storage Room',
            'risk_range' => 'High',
            'comment' => 'Flammable materials stored improperly. No fire-rated cabinet present. Emergency exit partially blocked by boxes.',
            'recommendations' => 'Install fire-rated storage cabinet. Relocate materials blocking exit. Post proper signage.',
            'resolved' => false,
        ],
        [
            'location' => 'Building B, Floor 1 - Open Office',
            'risk_range' => 'Low',
            'comment' => 'Minor wear on emergency exit signage. Illumination slightly dimmed.',
            'recommendations' => 'Replace exit sign bulbs during next maintenance cycle.',
            'resolved' => true,
        ],
        [
            'location' => 'Building B, Floor 2 - Conference Room A',
            'risk_range' => 'Medium',
            'comment' => 'Electrical outlet shows signs of overheating. Multiple power strips daisy-chained together.',
            'recommendations' => 'Have electrician inspect outlet. Remove daisy-chained power strips and install proper power distribution.',
            'resolved' => false,
        ],
        [
            'location' => 'Warehouse C - Section 1',
            'risk_range' => 'High',
            'comment' => 'Sprinkler heads obstructed by high-stacked inventory. Minimum 18-inch clearance not maintained.',
            'recommendations' => 'Reorganize inventory to maintain required clearance. Mark maximum stacking height on walls.',
            'resolved' => false,
        ],
        [
            'location' => 'Warehouse C - Section 2',
            'risk_range' => 'Medium',
            'comment' => 'Emergency lighting battery backup failed during test. Two of five units non-functional.',
            'recommendations' => 'Replace failed emergency lighting units. Implement quarterly testing schedule.',
            'resolved' => true,
        ],
        [
            'location' => 'Warehouse C - Loading Dock',
            'risk_range' => 'Low',
            'comment' => 'Fire lane markings faded and difficult to see. Last repainted 4 years ago.',
            'recommendations' => 'Repaint fire lane markings with high-visibility paint.',
            'resolved' => false,
        ],
        [
            'location' => 'Parking Structure - Level B1',
            'risk_range' => 'Medium',
            'comment' => 'Standpipe connection cap missing. Potential for debris contamination.',
            'recommendations' => 'Install new standpipe cap. Add to monthly inspection checklist.',
            'resolved' => true,
        ],
        [
            'location' => 'Building A, Basement - Electrical Room',
            'risk_range' => 'High',
            'comment' => 'Combustible materials stored within 3 feet of electrical panels. No fire extinguisher present in room.',
            'recommendations' => 'Remove all combustible materials. Install Class C fire extinguisher. Post warning signage.',
            'resolved' => false,
        ],
        [
            'location' => 'Building A, Roof - HVAC Area',
            'risk_range' => 'Low',
            'comment' => 'Minor debris accumulation around HVAC units. Potential fuel source if not addressed.',
            'recommendations' => 'Schedule roof cleaning. Add to seasonal maintenance routine.',
            'resolved' => false,
        ],
        [
            'location' => 'Building B, Basement - Boiler Room',
            'risk_range' => 'Medium',
            'comment' => 'Gas detector calibration expired. Unit showing error code. Last calibration 14 months ago.',
            'recommendations' => 'Recalibrate gas detector immediately. Set up annual calibration reminders.',
            'resolved' => false,
        ],
        [
            'location' => 'Main Campus - Exterior Fire Hydrant #3',
            'risk_range' => 'High',
            'comment' => 'Fire hydrant not accessible. Landscaping overgrowth blocking access. Visibility from road compromised.',
            'recommendations' => 'Clear vegetation immediately. Maintain 3-foot clearance. Paint hydrant for visibility.',
            'resolved' => true,
        ],
        [
            'location' => 'Building A, Floor 4 - Laboratory',
            'risk_range' => 'High',
            'comment' => 'Chemical storage cabinet ventilation not functioning. Strong odor detected. Flammable chemicals present.',
            'recommendations' => 'Repair ventilation system urgently. Temporarily relocate most hazardous materials.',
            'resolved' => false,
        ],
        [
            'location' => 'Building B, Floor 3 - Break Room',
            'risk_range' => 'Low',
            'comment' => 'Microwave positioned too close to paper towel dispenser. Minor fire risk.',
            'recommendations' => 'Relocate microwave or paper towel dispenser to maintain safe distance.',
            'resolved' => true,
        ],
        [
            'location' => 'Warehouse C - Office Area',
            'risk_range' => 'Medium',
            'comment' => 'Space heater in use despite policy prohibition. Extension cord shows wear.',
            'recommendations' => 'Remove space heater. Address underlying heating issue. Replace worn extension cord.',
            'resolved' => false,
        ],
        [
            'location' => 'Building A, Floor 1 - Reception',
            'risk_range' => 'Low',
            'comment' => 'Decorative items partially blocking sprinkler coverage pattern.',
            'recommendations' => 'Relocate decorative items to ensure full sprinkler coverage.',
            'resolved' => false,
        ],
        [
            'location' => 'Building B, Ground Floor - Utility Closet',
            'risk_range' => 'Medium',
            'comment' => 'Water heater showing signs of corrosion at base. Gas line connection area needs inspection.',
            'recommendations' => 'Schedule professional inspection of water heater and gas connections.',
            'resolved' => false,
        ],
        [
            'location' => 'Main Campus - Emergency Assembly Point A',
            'risk_range' => 'Low',
            'comment' => 'Assembly point signage weathered and partially illegible.',
            'recommendations' => 'Replace weathered signage with durable materials.',
            'resolved' => true,
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->sampleFindings as $data) {
            $finding = new Finding();
            $finding->setLocation($data['location']);
            $finding->setRiskRange($data['risk_range']);
            $finding->setComment($data['comment']);
            $finding->setRecommendations($data['recommendations']);
            $finding->setResolved($data['resolved']);

            $manager->persist($finding);
            $manager->flush();
            $manager->clear();
        }
    }
}
