<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SuperAdminFixtures extends Fixture
{
    private UserPasswordHasherInterface $hasher;

    public function __construct(
        UserPasswordHasherInterface $hasher,
    ) {
        $this->hasher = $hasher;
    }

    public function load(ObjectManager $manager): void
    {
        $superAdmin = $this->createSuperAdmin();

        $manager->persist($superAdmin);
        $manager->flush();
    }

    /**
     * Peremet de créer le super administrateur.
     */
    private function createSuperAdmin(): User
    {
        $superAdmin = new User();

        $superAdmin->setFirstName('Jean');
        $superAdmin->setLastName('Dupont');
        $superAdmin->setEmail('medecine-du-monde@gmail.com');
        $superAdmin->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);

        $passwordHashed = $this->hasher->hashPassword($superAdmin, 'azerty1234A*');
        $superAdmin->setPassword($passwordHashed);

        $superAdmin->setIsVerified(true);

        $superAdmin->setCreatedAt(new \DateTimeImmutable());
        $superAdmin->setUpdatedAt(new \DateTimeImmutable());
        $superAdmin->setVerifiedAt(new \DateTimeImmutable());

        return $superAdmin;
    }
}
