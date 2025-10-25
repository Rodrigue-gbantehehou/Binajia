<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

echo "Testing MembershipCardService fix...\n";

try {
    // Use the console application to bootstrap Symfony properly
    $kernel = new \App\Kernel('dev', true);
    $kernel->boot();
    $container = $kernel->getContainer();

    $membershipCardService = $container->get(\App\Service\MembershipCardService::class);
    $userRepo = $container->get(Doctrine\ORM\EntityManagerInterface::class)->getRepository(\App\Entity\User::class);

    $user = $userRepo->findOneBy([]);
    if (!$user) {
        echo "No test user found. Creating a test user...\n";

        // Create a test user
        $user = new \App\Entity\User();
        $user->setEmail('test@example.com');
        $user->setFirstname('Test');
        $user->setLastname('User');
        $user->setPhone('+22912345678');
        $user->setCreatedAt(new DateTime());

        $em = $container->get(Doctrine\ORM\EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        echo "Test user created with ID: " . $user->getId() . "\n";
    }

    echo "Testing PDF generation...\n";
    $result = $membershipCardService->generateAndPersist($user, null, 'test-avatar.jpg', 'TEST-' . date('Y') . '-001');

    echo "SUCCESS: PDF generated without type errors!\n";
    echo "Result type: " . gettype($result) . "\n";
    echo "Result keys: " . implode(', ', array_keys($result)) . "\n";

    // Check if paths are strings
    foreach ($result as $key => $value) {
        echo "$key: " . gettype($value) . " - ";
        if (is_string($value)) {
            echo "String length: " . strlen($value) . "\n";
        } else {
            echo "Value: " . var_export($value, true) . "\n";
        }
    }

} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
