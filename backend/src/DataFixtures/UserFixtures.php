<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create Admin user
        $admin = new User();
        $admin->setEmail('admin@firerisk.local');
        $admin->setName('Admin User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setActive(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));

        $manager->persist($admin);

        // Create Regular user
        $user = new User();
        $user->setEmail('user@firerisk.local');
        $user->setName('Regular User');
        $user->setRoles([]);
        $user->setActive(true);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));

        $manager->persist($user);

        $manager->flush();
    }
}
