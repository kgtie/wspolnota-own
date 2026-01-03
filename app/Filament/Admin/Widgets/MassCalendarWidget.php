<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Mass;
use Filament\Facades\Filament;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Guava\Calendar\Enums\CalendarViewType;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\DateClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Guava\Calendar\ValueObjects\DateSelectInfo;

class MassCalendarWidget extends CalendarWidget
{
    // startuj w miesiącu, żeby od razu było widać eventy
    protected CalendarViewType $calendarView = CalendarViewType::TimeGridWeek;

    protected bool $dateClickEnabled = true;
    protected bool $eventClickEnabled = true;
    protected bool $selectEnabled = true;
    protected bool $dateSelectEnabled = true;
    protected bool $selectMirrorEnabled = true;
    protected ?string $defaultEventClickAction = 'edit';

    public static function canView(): bool
    {
        // pokaż tylko na stronie kalendarza, nie na dashboardzie
        return request()?->routeIs('filament.admin.pages.mass-calendar') === true;
    }

    protected function getEvents(FetchInfo $info): Collection|array|Builder
    {
        $tenant = Filament::getTenant();

        return Mass::query()
            ->where('parish_id', $tenant->id)
            ->whereBetween('start_time', [$info->start, $info->end]);
    }

    protected function onDateClick(DateClickInfo $info): void
    {
        $this->mountAction('createMass', ['dateClick' => $info]);
    }

    public function createMassAction()
    {
        return $this->createAction(Mass::class)
            ->mountUsing(function (array $arguments = []) {
                if (isset($arguments['dateClick'])) {
                    /** @var \Guava\Calendar\ValueObjects\DateClickInfo $info */
                    $info = $arguments['dateClick'];

                    $this->form->fill([
                        'start_time' => $info->date->setTime(18, 0),
                    ]);

                    return;
                }

                if (isset($arguments['select'])) {
                    /** @var \Guava\Calendar\ValueObjects\DateSelectInfo $info */
                    $info = $arguments['select'];

                    $this->form->fill([
                        'start_time' => $info->start, // CarbonImmutable
                    ]);

                    return;
                }
            })
            ->mutateDataUsing(function (array $data) {
                $data['parish_id'] = Filament::getTenant()->id;
                return $data;
            });
    }

    protected function onSelect(DateSelectInfo $info): void
    {
        $this->mountAction('create', [
            'select' => $info,
        ]);
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('viewWeek')
                ->label('Tydzień')
                ->icon('heroicon-o-calendar-days')
                ->action(fn () => $this->switchView(CalendarViewType::TimeGridWeek))
                ->color(fn () => $this->currentView() === CalendarViewType::TimeGridWeek ? 'primary' : 'gray'),

            Action::make('viewMonth')
                ->label('Miesiąc')
                ->icon('heroicon-o-calendar')
                ->action(fn () => $this->switchView(CalendarViewType::DayGridMonth))
                ->color(fn () => $this->currentView() === CalendarViewType::DayGridMonth ? 'primary' : 'gray'),
        ];
    }

    protected function currentView(): CalendarViewType
    {
        // jeśli jeszcze nie przełączano, opieramy się o domyślny
        return $this->calendarView;
    }

    protected function switchView(CalendarViewType $view): void
    {
        // zapamiętujemy stan (żeby kolor przycisków był spójny)
        $this->calendarView = $view;

        // KLUCZ: zmiana widoku w runtime przez setOption()
        $this->setOption('view', $view->value);
    }

    public function massSchema(Schema $schema): Schema
    {
        return $schema->components([
            DateTimePicker::make('start_time')->label('Data i godzina')->seconds(false)->required(),

            Select::make('location')
                ->label('Miejsce')
                ->options(array_combine(Mass::getLocationOptions(), Mass::getLocationOptions()))
                ->searchable()
                ->required(),

            TextInput::make('celebrant')->label('Celebrans')->maxLength(255),

            Textarea::make('intention')->label('Intencja')->rows(4)->required(),

            Select::make('type')->label('Rodzaj')->options(Mass::getTypeOptions())->required(),
            Select::make('rite')->label('Ryt')->options(Mass::getRiteOptions())->required(),

            TextInput::make('stipend')->label('Stypendium')->numeric()->prefix('zł'),
        ])->columns(2);
    }
}
