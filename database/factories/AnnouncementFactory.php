<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\AnnouncementSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * Przykładowe treści ogłoszeń parafialnych
     */
    protected array $announcements = [
        'W tym tygodniu przypada I piątek miesiąca. Spowiedź od godz. 16:00.',
        'Serdecznie zapraszamy na adorację Najświętszego Sakramentu w każdy czwartek po Mszy św. wieczornej.',
        'Caritas parafialna organizuje zbiórkę żywności dla potrzebujących. Dary można przynosić do zakrystii.',
        'W przyszłą niedzielę po każdej Mszy św. przed kościołem odbędzie się zbiórka do puszek na misje.',
        'Przypominamy o możliwości zamawiania intencji mszalnych w zakrystii lub kancelarii parafialnej.',
        'Spotkanie Rady Parafialnej odbędzie się we wtorek o godz. 19:00 w salce parafialnej.',
        'Młodzież przygotowującą się do bierzmowania zapraszamy na spotkanie formacyjne w piątek o godz. 18:00.',
        'Dzieci pierwszokomunijne wraz z rodzicami zapraszamy na spotkanie w sobotę o godz. 10:00.',
        'Zachęcamy do prenumeraty czasopism katolickich: Gość Niedzielny, Niedziela, Mały Gość.',
        'Parafialny Klub Seniora zaprasza na spotkanie w środę o godz. 15:00 w Domu Parafialnym.',
        'Za tydzień po wszystkich Mszach św. odbędzie się błogosławieństwo pokarmów wielkanocnych.',
        'Nabożeństwa różańcowe w październiku codziennie o godz. 17:30.',
        'Roraty w Adwencie codziennie o godz. 6:30. Zapraszamy wszystkie dzieci!',
        'Droga Krzyżowa w każdy piątek Wielkiego Postu o godz. 17:30.',
        'Gorzkie Żale w każdą niedzielę Wielkiego Postu o godz. 17:00.',
        'W najbliższą sobotę sprzątanie kościoła. Prosimy chętnych o pomoc.',
        'Zapraszamy na pielgrzymkę parafialną do Częstochowy. Zapisy w zakrystii.',
        'Nabożeństwo majowe w dni powszednie o godz. 18:00, w niedziele po Mszy św. o 11:00.',
        'Zapraszamy na Noc Konfesjonałów w najbliższy piątek od godz. 20:00 do 24:00.',
        'Trwa remont dachu kościoła. Za wszelką pomoc finansową serdecznie dziękujemy.',
        'Nadzwyczajni Szafarze Komunii Św. - spotkanie formacyjne w sobotę o godz. 9:00.',
        'Zapraszamy na rekolekcje parafialne od piątku do niedzieli. Szczegółowy program na tablicy ogłoszeń.',
    ];

    public function definition(): array
    {
        static $sortOrder = 0;
        $sortOrder++;

        return [
            'announcement_set_id' => AnnouncementSet::factory(),
            'content' => '<p>' . fake()->randomElement($this->announcements) . '</p>',
            'sort_order' => $sortOrder % 10,
            'is_highlighted' => fake()->boolean(15), // 15% szans na wyróżnienie
        ];
    }

    /**
     * Ogłoszenie wyróżnione
     */
    public function highlighted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_highlighted' => true,
        ]);
    }

    /**
     * Reset licznika kolejności
     */
    public function resetSortOrder(): static
    {
        return $this->state(fn (array $attributes) => [
            'sort_order' => 0,
        ]);
    }
}
