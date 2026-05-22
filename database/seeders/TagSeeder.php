<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Tags\Tag;

class TagSeeder extends Seeder
{
    /** @var list<string> */
    private const TAGS = [
        // Languages
        'PHP', 'JavaScript', 'TypeScript', 'Python', 'Go', 'Java', 'SQL',
        // Frameworks & Libraries
        'Laravel', 'Vue.js', 'React', 'Node.js', 'Symfony', 'Tailwind CSS',
        // Databases
        'MySQL', 'PostgreSQL', 'Redis', 'MongoDB', 'SQLite',
        // Infrastructure & DevOps
        'Docker', 'Kubernetes', 'Linux', 'Git', 'CI/CD', 'Nginx',
        // Concepts & Patterns
        'REST API', 'GraphQL', 'SOLID', 'Design Patterns', 'OOP',
        'Algorithms', 'Data Structures', 'Testing', 'Security',
        // Architecture
        'Microservices', 'Event-Driven', 'Domain-Driven Design', 'Clean Architecture',
    ];

    public function run(): void
    {
        foreach (self::TAGS as $name) {
            Tag::findOrCreate($name);
        }
    }
}
