<?php
declare(strict_types=1);

namespace App\Repository;

use App\Core\Database;

final class UserRepository
{
    public function findByEmail(string $email): array|false
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM users WHERE email = :email LIMIT 1'
        );

        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }
}