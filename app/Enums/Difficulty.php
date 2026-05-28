<?php

declare(strict_types=1);

namespace App\Enums;

enum Difficulty: string
{
    case Junior = 'junior';
    case Mid = 'mid';
    case Senior = 'senior';

    public function label(): string
    {
        return match ($this) {
            self::Junior => 'Junior',
            self::Mid => 'Mid',
            self::Senior => 'Senior',
        };
    }

    public function promptDescription(): string
    {
        return match ($this) {
            self::Junior => 'Junior (do 2 lat doświadczenia) — sprawdzaj wyłącznie podstawy: składnię języka, typy danych, proste wzorce MVC, CRUD, podstawy SQL (SELECT/JOIN/WHERE), obsługę błędów try/catch, zmienne sesji. NIE pytaj o wzorce projektowe, DDD, CQRS, skalowanie ani architekturę systemów.',
            self::Mid => 'Mid (2–5 lat doświadczenia) — sprawdzaj: wzorce projektowe (Repository, Service Layer, Observer, Factory), testy jednostkowe i integracyjne, optymalizację zapytań SQL (indeksy, N+1), REST API design, kolejki (Jobs/queues), cache (Redis), refaktoryzację kodu. Pytania powinny wymagać umiejętności uzasadnienia decyzji i znalezienia problemu w kodzie.',
            self::Senior => 'Senior (5+ lat doświadczenia) — sprawdzaj: architekturę systemu (CQRS, Event Sourcing, Hexagonal Architecture, DDD), decyzje trade-off pod presją czasu/budżetu, skalowanie horyzontalne i wertykalne, bezpieczeństwo aplikacji (OWASP), projektowanie API od zera, mentoring i code review, migracje dużych baz danych bez downtime, obsługę distributed systems.',
        };
    }
}
