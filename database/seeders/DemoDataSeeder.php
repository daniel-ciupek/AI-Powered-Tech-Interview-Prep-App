<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Difficulty;
use App\Enums\QuestionSource;
use App\Enums\SessionStatus;
use App\Models\InterviewSession;
use App\Models\Question;
use App\Models\Repetition;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One-off seeder that populates the demo account (test@example.com by default)
 * with a realistic-looking dataset for screenshots and live demos:
 *   - ~30 questions across all difficulty tiers
 *   - repetitions in every SM-2 state (due now, due soon, learned)
 *   - 60 days of backdated review logs with a believable quality distribution
 *   - completed + in-progress interview sessions with messages
 *
 * Run with: `./vendor/bin/sail artisan db:seed --class=DemoDataSeeder`
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstWhere('email', 'test@example.com')
            ?? User::factory()->create(['email' => 'test@example.com', 'name' => 'Demo User']);

        $user->forceFill([
            'onboarded_at' => $user->onboarded_at ?? now(),
            'preferred_difficulty' => Difficulty::Mid,
            'daily_goal' => 10,
            'streak_count' => 7,
            'last_studied_at' => now(),
            'theme' => 'system',
        ])->save();

        $this->wipeUserData($user);

        $questions = $this->createQuestions($user);
        $this->createReviewHistory($user, $questions);
        $this->createInterviewSessions($user);
    }

    private function wipeUserData(User $user): void
    {
        DB::table('review_logs')
            ->whereIn('repetition_id', $user->repetitions()->pluck('id'))
            ->delete();
        $user->repetitions()->delete();
        $user->questions()->delete();
        DB::table('interview_messages')
            ->whereIn('session_id', $user->interviewSessions()->pluck('id'))
            ->delete();
        $user->interviewSessions()->delete();
    }

    /**
     * @return array<int, Question>
     */
    private function createQuestions(User $user): array
    {
        $bank = [
            [Difficulty::Junior, 'Czym różni się `==` od `===` w PHP?', ['typowanie', 'porównanie', 'rzutowanie'], 'Operator `==` porównuje wartości po koercji typu, `===` sprawdza wartość i typ.'],
            [Difficulty::Junior, 'Wyjaśnij różnicę między `let`, `const` i `var` w JavaScript.', ['scope', 'hoisting', 'block'], '`var` ma function-scope i jest hoistowany. `let` i `const` mają block-scope; `const` blokuje re-assignment referencji.'],
            [Difficulty::Junior, 'Co to jest dependency injection?', ['IoC', 'container', 'binding'], 'Wzorzec, w którym zależności wstrzykuje się z zewnątrz (zwykle przez konstruktor) zamiast tworzyć je w klasie.'],
            [Difficulty::Junior, 'Czym jest middleware w Laravel?', ['request', 'pipeline', 'handler'], 'Warstwa filtrów dla request/response w pipeline aplikacji — autoryzacja, throttling, CSRF.'],
            [Difficulty::Junior, 'Wyjaśnij REST.', ['stateless', 'resource', 'verbs'], 'Architektura komunikacji oparta o zasoby, stateless żądania i standardowe metody HTTP.'],
            [Difficulty::Junior, 'Co to są indeksy w bazie danych?', ['B-tree', 'lookup', 'performance'], 'Struktury danych przyspieszające wyszukiwanie kosztem zapisu i miejsca.'],
            [Difficulty::Junior, 'Czym jest CORS?', ['origin', 'preflight', 'headers'], 'Mechanizm pozwalający przeglądarce na żądania cross-origin pod warunkiem odpowiednich nagłówków.'],
            [Difficulty::Junior, 'Wyjaśnij Promise w JavaScript.', ['async', 'resolve', 'reject'], 'Obiekt reprezentujący przyszłą wartość operacji asynchronicznej — pending, fulfilled albo rejected.'],
            [Difficulty::Junior, 'Czym jest SOLID?', ['srp', 'ocp', 'dip'], 'Pięć zasad projektowania OO: SRP, OCP, LSP, ISP, DIP.'],
            [Difficulty::Junior, 'Co to jest service worker?', ['pwa', 'cache', 'offline'], 'Skrypt działający w tle przeglądarki, niezależny od strony — obsługuje cache, push, offline.'],

            [Difficulty::Mid, 'Wyjaśnij N+1 i jak go wykryć.', ['eager loading', 'with', 'lazy'], 'N+1 = jedno query na listę + N na każdy element. Wykrywanie: telescope, debugbar, `Model::preventLazyLoading()`.'],
            [Difficulty::Mid, 'Kiedy użyć kolejki zamiast synchronicznego wywołania?', ['queue', 'job', 'failed'], 'Gdy operacja jest wolna, kosztowna lub może padać i wymaga retry (e.g. wysyłka maili, AI calls).'],
            [Difficulty::Mid, 'Jak działa zwracanie typowane (covariant returns) w PHP 8?', ['contravariance', 'liskov', 'override'], 'Klasy potomne mogą zawężać typ zwracany (covariance) a rozszerzać typ parametrów (contravariance).'],
            [Difficulty::Mid, 'Wyjaśnij CSRF i jak Laravel mu zapobiega.', ['token', 'session', 'middleware'], 'CSRF wymusza akcję w imieniu zalogowanego usera. Laravel używa tokena w sesji + nagłówek/pole formularza.'],
            [Difficulty::Mid, 'Czym różnią się `INNER JOIN` od `LEFT JOIN`?', ['nullable', 'matching', 'result'], '`INNER JOIN` zwraca tylko dopasowane wiersze. `LEFT JOIN` zwraca wszystkie z lewej tabeli + NULL dla braku dopasowania.'],
            [Difficulty::Mid, 'Wyjaśnij optimistic locking.', ['version', 'conflict', 'retry'], 'Wykrywa konflikt zapisu przez kolumnę wersji — jeśli wersja zmienna się od czasu odczytu, transakcja faila.'],
            [Difficulty::Mid, 'Co to jest event sourcing?', ['append-only', 'aggregate', 'projection'], 'Stan aplikacji wyprowadzany ze strumienia eventów zamiast bezpośrednich mutacji tabel.'],
            [Difficulty::Mid, 'Wyjaśnij algorytm SM-2.', ['ease factor', 'interval', 'quality'], 'Spaced repetition: kolejny interwał = poprzedni × ease, ease ulega korekcie po każdej odpowiedzi (q=0..5).'],
            [Difficulty::Mid, 'Czym jest CSP (Content Security Policy)?', ['xss', 'directive', 'header'], 'Nagłówek HTTP definiujący jakie źródła skryptów/styli/obrazów może ładować strona.'],
            [Difficulty::Mid, 'Wyjaśnij różnicę między `JWT` a `session-based auth`.', ['stateless', 'cookie', 'revoke'], 'JWT jest stateless (claims w tokenie), session używa cookie + storage po stronie serwera.'],

            [Difficulty::Senior, 'Jak zaprojektowałbyś rate limiting dla API z user-scoped quota?', ['redis', 'sliding window', 'token bucket'], 'Najczęściej Redis + sliding window log lub token bucket per user_id, z fallback w pamięci dla edge cases.'],
            [Difficulty::Senior, 'Wyjaśnij CAP theorem.', ['consistency', 'availability', 'partition'], 'W rozproszonym systemie nie da się jednocześnie zagwarantować consistency, availability i partition tolerance — wybór 2 z 3.'],
            [Difficulty::Senior, 'Jak migrować NOT NULL bez downtime na tabeli 100M wierszy?', ['expand-contract', 'backfill', 'default'], 'Faza 1: dodaj kolumnę nullable + default. Faza 2: backfill batchami. Faza 3: enforce NOT NULL po pełnym backfillu.'],
            [Difficulty::Senior, 'Wyjaśnij idempotencję w REST API.', ['retry', 'safe', 'put'], 'Wielokrotne wywołanie daje ten sam efekt jak pojedyncze. GET, PUT, DELETE — idempotentne. POST — zwykle nie.'],
            [Difficulty::Senior, 'Kiedy warto użyć CQRS?', ['read model', 'write model', 'eventual'], 'Gdy obciążenie odczytu/zapisu się rozjeżdża, lub gdy modele domeny i widoki bardzo się różnią.'],
            [Difficulty::Senior, 'Jak debugować memory leak w długo żyjącym workerze PHP?', ['retain', 'gc_collect_cycles', 'restart'], 'Profile retain w xdebug/blackfire, używaj `--max-time` w queue:work, monitor RSS przez supervisor.'],
            [Difficulty::Senior, 'Wyjaśnij saga pattern.', ['compensation', 'distributed', 'transaction'], 'Sekwencja lokalnych transakcji z kompensacjami — zamiast rozproszonej transakcji ACID, używamy sterowanego rollbacku.'],
            [Difficulty::Senior, 'Jak zaprojektować bezpieczne przechowywanie kluczy API użytkowników?', ['encryption at rest', 'KMS', 'rotation'], 'Szyfrowanie kolumnowe z `Crypt::encryptString` + APP_KEY rotation, audit log dostępu, RBAC.'],
        ];

        $questions = [];
        foreach ($bank as $index => [$difficulty, $content, $keywords, $answer]) {
            $createdAt = now()->subDays(rand(0, 60))->subMinutes(rand(0, 1440));
            $question = $user->questions()->create([
                'content' => $content,
                'expected_answer' => $answer,
                'expected_keywords' => $keywords,
                'difficulty' => $difficulty,
                'source' => $index % 7 === 0 ? QuestionSource::UserCreated : QuestionSource::AiGenerated,
            ]);
            DB::table('questions')->where('id', $question->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
            $questions[] = $question->fresh();
        }

        return $questions;
    }

    /**
     * @param  array<int, Question>  $questions
     */
    private function createReviewHistory(User $user, array $questions): void
    {
        foreach ($questions as $question) {
            // ~80% have a repetition; 20% are fresh "unstudied" questions.
            if (rand(1, 100) > 80) {
                continue;
            }
            $rep = $this->seedRepetition($user, $question);
            $this->seedReviewLogs($rep);
        }

        // Make sure today shows progress.
        $todays = $user->repetitions()->inRandomOrder()->limit(4)->get();
        foreach ($todays as $rep) {
            $this->insertReviewLog($rep, quality: rand(3, 5), createdAt: now()->subMinutes(rand(5, 240)));
        }
    }

    private function seedRepetition(User $user, Question $question): Repetition
    {
        $reps = rand(0, 6);
        $ease = round(max(1.30, 2.50 + ((rand(-30, 20) / 100))), 2);
        $interval = $reps === 0 ? 0 : (int) round(min(60, $ease ** $reps));
        $isDue = rand(1, 100) <= 35;
        $nextReview = $isDue
            ? now()->subDays(rand(0, 5))
            : now()->addDays(rand(1, 30));
        $lastReviewed = $reps === 0 ? null : now()->subDays(rand(1, 14));

        return $user->repetitions()->create([
            'question_id' => $question->id,
            'ease_factor' => $ease,
            'interval_days' => $interval,
            'repetitions_count' => $reps,
            'quality_last' => $reps === 0 ? null : rand(2, 5),
            'next_review_at' => $nextReview,
            'last_reviewed_at' => $lastReviewed,
        ]);
    }

    private function seedReviewLogs(Repetition $rep): void
    {
        $count = $rep->repetitions_count + rand(0, 3);
        for ($i = 0; $i < $count; $i++) {
            $daysAgo = rand(1, 60);
            // 65% chance of success (q >= 3), 35% chance of struggle (q < 3)
            $quality = rand(1, 100) <= 65 ? rand(3, 5) : rand(0, 2);
            $this->insertReviewLog($rep, quality: $quality, createdAt: now()->subDays($daysAgo));
        }
    }

    private function insertReviewLog(Repetition $rep, int $quality, Carbon $createdAt): void
    {
        DB::table('review_logs')->insert([
            'repetition_id' => $rep->id,
            'quality' => $quality,
            'ease_before' => $rep->ease_factor,
            'ease_after' => $rep->ease_factor,
            'interval_before' => $rep->interval_days,
            'interval_after' => $rep->interval_days,
            'created_at' => $createdAt,
        ]);
    }

    private function createInterviewSessions(User $user): void
    {
        InterviewSession::create([
            'user_id' => $user->id,
            'topic_tags' => ['laravel', 'queues', 'redis'],
            'difficulty' => Difficulty::Mid,
            'status' => SessionStatus::Completed,
            'final_report' => "## Mocne strony\n- Solidna znajomość mechanizmu kolejek w Laravelu\n- Trafnie wymieniona różnica między `--max-time` a `--once`\n\n## Obszary do poprawy\n- Brak wzmianki o failed_jobs i mechanizmie retry\n- Idempotencja jobów nie została omówiona\n\n## Werdykt\nDobra znajomość podstaw, ale produkcyjny edge-case retry mógłby być pokryty głębiej.",
            'tokens_used_total' => 2840,
            'started_at' => now()->subDays(3)->setTime(20, 15),
            'ended_at' => now()->subDays(3)->setTime(20, 42),
        ]);

        InterviewSession::create([
            'user_id' => $user->id,
            'topic_tags' => ['php', 'oop', 'design patterns'],
            'difficulty' => Difficulty::Senior,
            'status' => SessionStatus::Completed,
            'final_report' => "## Mocne strony\n- Pewne odpowiedzi o SOLID\n- Dobry przykład Decorator vs Adapter\n\n## Obszary do poprawy\n- Brak praktycznego use-case dla CQRS w Laravelu\n- Słabo omówiona różnica między event sourcing a event-driven\n\n## Werdykt\nPoziom mid+. Brakuje doświadczenia z większymi systemami rozproszonymi.",
            'tokens_used_total' => 4120,
            'started_at' => now()->subDays(8)->setTime(19, 0),
            'ended_at' => now()->subDays(8)->setTime(19, 35),
        ]);

        $active = InterviewSession::create([
            'user_id' => $user->id,
            'topic_tags' => ['vue', 'inertia', 'spa'],
            'difficulty' => Difficulty::Mid,
            'status' => SessionStatus::Active,
            'tokens_used_total' => 620,
            'started_at' => now()->subMinutes(7),
        ]);

        $convo = [
            ['assistant', 'Cześć! Zacznijmy od podstaw — wyjaśnij własnymi słowami, czym różni się Inertia od klasycznego SPA z REST API.'],
            ['user', 'Inertia łączy frontend SPA z backendem monolitowym — kontroler Laravela zwraca strony Inertii zamiast JSONa, a klient renderuje komponent Vue z propami z serwera.'],
            ['assistant', 'Dobrze. Co się dzieje pod spodem, gdy klikam `<Link>` Inertii w Vue?'],
            ['user', 'Inertia robi XHR z nagłówkiem `X-Inertia: true`, backend zwraca JSON `{component, props, url, version}`, a klient zamienia historię + podstawia nowy komponent bez full page reload.'],
            ['assistant', 'Dokładnie. Teraz trudniej — jak zaimplementowałbyś flash messages przekazywane z kontrolera do widoku tak, żeby nie trafiły do kolejnego żądania?'],
        ];

        foreach ($convo as $i => [$role, $content]) {
            DB::table('interview_messages')->insert([
                'session_id' => $active->id,
                'role' => $role,
                'content' => $content,
                'tokens_used' => $role === 'assistant' ? rand(80, 150) : rand(50, 110),
                'created_at' => now()->subMinutes(7 - $i),
            ]);
        }
    }
}
