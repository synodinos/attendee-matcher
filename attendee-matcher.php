<?php

/*
    This script uses the Hungarian Algorithm to optimally pair conference attendees based on their interests, roles, country of origin, industry, and company size. The algorithm ensures the best possible matching while avoiding very low-score pairings.
    
    Required Composer Package:
    - elgigi/hungarian-algorithm: Implements the Hungarian Algorithm for solving assignment problems efficiently.
    
    To install the package, run:
    composer require elgigi/hungarian-algorithm
*/

require 'vendor/autoload.php'; // Ensure elgigi/hungarian-algorithm is installed

use ElGigi\HungarianAlgorithm\Hungarian;

/*
    Generate 150 attendees with random attributes for testing.
    Each attendee has:
    - A personal interest
    - A role
    - A country of origin
    - An industry
    - A company size (categorized into industry-accepted ranges)
*/
$attendees = [];
$interests = ['AI', 'Cloud', 'Security', 'IoT', 'Microservices'];
$roles = ['Dev', 'PM', 'Architect', 'Tester', 'Manager'];
$similar_roles = [
    'Dev' => ['Architect', 'Tester'],
    'PM' => ['Manager'],
    'Architect' => ['Dev'],
    'Tester' => ['Dev'],
    'Manager' => ['PM']
];
$countries = ['US', 'UK', 'Germany', 'France', 'Canada'];
$industries = ['Tech', 'Finance', 'Healthcare', 'Education', 'Retail'];
$similar_industries = [
    'Tech' => ['Finance'],
    'Finance' => ['Tech', 'Retail'],
    'Healthcare' => ['Education'],
    'Education' => ['Healthcare'],
    'Retail' => ['Finance']
];

$company_size_ranges = [
    'Small' => [1, 50],
    'Medium' => [51, 500],
    'Large' => [501, 5000],
    'Enterprise' => [5001, 50000]
];

for ($i = 0; $i < 150; $i++) {
    $size = rand(1, 50000);
    foreach ($company_size_ranges as $range => [$min, $max]) {
        if ($size >= $min && $size <= $max) {
            $company_size_category = $range;
            break;
        }
    }
    
    $attendees[] = [
        "id" => $i,
        "interest" => $interests[array_rand($interests)],
        "role" => $roles[array_rand($roles)],
        "country" => $countries[array_rand($countries)],
        "industry" => $industries[array_rand($industries)],
        "company_size" => $company_size_category,
    ];
}

$n = count($attendees);
$costMatrix = array_fill(0, $n, array_fill(0, $n, 0));

/*
    Define a minimum acceptable score threshold.
    If a match has a score lower than this threshold,
    it will be given a high cost to discourage selection.
*/
$MIN_ACCEPTABLE_SCORE = 5;
$HIGH_COST = 9999; // Large value to discourage bad matches

/*
    Compute cost matrix based on similarity scoring.
    Higher scores indicate better matches.
    - +10 if the same interest
    - +8 if the same role
    - +5 if the roles are similar
    - +2 if the same country
    - +7 if the same company size category
    - +5 if the same industry
    - +2 if the industries are similar
    - -10 if company sizes differ by more than one category
*/
for ($i = 0; $i < $n; $i++) {
    for ($j = 0; $j < $n; $j++) {
        if ($i !== $j) {
            $score = 0;
            if ($attendees[$i]['interest'] === $attendees[$j]['interest']) $score += 10;
            if ($attendees[$i]['role'] === $attendees[$j]['role']) $score += 8;
            if (isset($similar_roles[$attendees[$i]['role']]) && in_array($attendees[$j]['role'], $similar_roles[$attendees[$i]['role']])) $score += 5;
            if ($attendees[$i]['country'] === $attendees[$j]['country']) $score += 2;
            if ($attendees[$i]['industry'] === $attendees[$j]['industry']) $score += 5;
            if (isset($similar_industries[$attendees[$i]['industry']]) && in_array($attendees[$j]['industry'], $similar_industries[$attendees[$i]['industry']])) $score += 2;
            if ($attendees[$i]['company_size'] === $attendees[$j]['company_size']) $score += 7;
            if (abs(array_search($attendees[$i]['company_size'], array_keys($company_size_ranges)) - array_search($attendees[$j]['company_size'], array_keys($company_size_ranges))) > 1) $score -= 10;
            
            // Avoid very low scores by setting a minimum threshold
            $costMatrix[$i][$j] = ($score < $MIN_ACCEPTABLE_SCORE) ? $HIGH_COST : -$score;
        } else {
            $costMatrix[$i][$j] = $HIGH_COST; // Large value to prevent self-matching
        }
    }
}

/*
    Apply the Hungarian Algorithm to find the optimal pairings.
    The algorithm minimizes total cost (or maximizes total match score) 
    by selecting the best possible assignment based on the computed cost matrix.
*/
$hungarian = new Hungarian($costMatrix);
$assignments = $hungarian->solve();

/*
    Display the optimal pairs based on the algorithm's output.
*/
echo "Optimal Pairs (Hungarian Algorithm):\n";
foreach ($assignments as $attendee1 => $attendee2) {
    echo "Attendee {$attendee1} (Interest: {$attendees[$attendee1]['interest']}, Role: {$attendees[$attendee1]['role']}, Country: {$attendees[$attendee1]['country']}, Industry: {$attendees[$attendee1]['industry']}, Company Size: {$attendees[$attendee1]['company_size']}) " .
         "matched with Attendee {$attendee2} (Interest: {$attendees[$attendee2]['interest']}, Role: {$attendees[$attendee2]['role']}, Country: {$attendees[$attendee2]['country']}, Industry: {$attendees[$attendee2]['industry']}, Company Size: {$attendees[$attendee2]['company_size']})\n";
}
?>
