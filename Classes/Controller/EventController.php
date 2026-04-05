<?php
namespace Nkfire\RescueReports\Controller;

use Nkfire\RescueReports\Domain\Model\Event;
use Nkfire\RescueReports\Domain\Repository\EventRepository;
use Nkfire\RescueReports\Domain\Repository\StationRepository;
use Nkfire\RescueReports\Domain\Repository\TypeRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class EventController extends ActionController
{
    protected EventRepository $eventRepository;
    protected TypeRepository $typeRepository;
    protected StationRepository $stationRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function injectTypeRepository(TypeRepository $typeRepository): void
    {
        $this->typeRepository = $typeRepository;
    }

    public function injectStationRepository(StationRepository $stationRepository): void
    {
        $this->stationRepository = $stationRepository;
    }

    /**
     * Liste aller Einsätze (mit optionalen FlexForm-Filtern)
     */
    public function listAction(?string $searchWord = null, ?string $station = null): ResponseInterface
    {
        $maxCount = (int)($this->settings['maxCount'] ?? 0);
        $dateFromValue = $this->settings['dateFrom'] ?? null;
        $dateToValue = $this->settings['dateTo'] ?? null;
        $enableSearch = (bool)($this->settings['enableSearch'] ?? false);
        $templateVariant = (string)($this->settings['templateVariant'] ?? 'standard');
        $detailPageUid = $this->normalizeDetailPageUid($this->settings['detailPageUid'] ?? null);

        $defaultStationUid = (int)($this->settings['defaultStation'] ?? 0);
        $selectedStationUid = $this->normalizeRecordUid($station);
        $activeStationUid = $selectedStationUid > 0 ? $selectedStationUid : $defaultStationUid;
        
        if ($activeStationUid === 0) {
            // Fallback: erste Station nehmen
            $firstStation = $this->stationRepository->findPrimaryBrigadeStations()->getFirst();
            if ($firstStation) {
                $activeStationUid = (int)$firstStation->getUid();
            }
        }

        $allowedTemplateVariants = [
            'standard',
            'sidebar',
            'newdesign',
            'newdesignsidebar',
        ];

        if (!in_array($templateVariant, $allowedTemplateVariants, true)) {
            $templateVariant = 'standard';
        }

        $searchWord = trim((string)($searchWord ?? ''));

        // Rohwerte aus FlexForm an Repository weitergeben
        $dateFrom = $dateFromValue;
        $dateTo = $dateToValue;

        if ($activeStationUid > 0) {
            if ($enableSearch && $searchWord !== '') {
                $events = $this->eventRepository->searchByStation(
                    $activeStationUid,
                    $searchWord,
                    $dateFrom,
                    $dateTo,
                    $maxCount
                );
            } else {
                $events = $this->eventRepository->findFilteredByStation(
                    $activeStationUid,
                    $dateFrom,
                    $dateTo,
                    $maxCount
                );
            }
        } else {
            if ($enableSearch && $searchWord !== '') {
                $events = $this->eventRepository->search($searchWord, $dateFrom, $dateTo, $maxCount);
            } else {
                $events = $this->eventRepository->findFiltered($dateFrom, $dateTo, $maxCount);
            }
        }

        $eventItems = $this->buildEventItemsForStations($events, $activeStationUid);
        $stations = $this->stationRepository->findPrimaryBrigadeStations();

        $this->view->assignMultiple([
            'events' => $events,
            'eventItems' => $eventItems,
            'stations' => $stations,
            'searchWord' => $searchWord,
            'enableSearch' => $enableSearch,
            'maxCount' => $maxCount,
            'dateFrom' => $this->createDateTimeFromFlexFormValue($dateFromValue),
            'dateTo' => $this->createDateTimeFromFlexFormValue($dateToValue),
            'templateVariant' => $templateVariant,
            'detailPageUid' => $detailPageUid,
            'defaultStationUid' => $defaultStationUid,
            'activeStationUid' => $activeStationUid,
            'settings' => $this->settings,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Detailansicht eines einzelnen Einsatzes
     */
    public function showAction(Event $event): ResponseInterface
    {
        $event = $this->eventRepository->findByUid($event->getUid());
        $groupedVehicleData = $this->groupVehiclesByBrigadeAndStation($event);

        $this->view->assignMultiple([
            'event' => $event,
            'groupedVehicleData' => $groupedVehicleData,
            'detailPageUid' => $this->normalizeDetailPageUid($this->settings['detailPageUid'] ?? null),
            'defaultStationUid' => (int)($this->settings['defaultStation'] ?? 0),
            'templateVariant' => (string)($this->settings['templateVariant'] ?? 'standard'),
            'settings' => $this->settings,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Baut View-Daten für dynamische Einsatznummern pro Station auf.
     *
     * Ein Einsatz kann mehrere Stationsnummern haben, z.B.:
     * ZÖ/030 und STD/005
     */
    protected function buildEventItemsForStations(iterable $events, int $selectedStationUid = 0): array
    {
        $items = [];

        foreach ($events as $event) {
            if (!$event instanceof Event) {
                continue;
            }

            $start = $event->getStart();
            $stationNumbers = [];
            $primaryNumber = '';
            $primaryStationName = '';

            if ($start instanceof \DateTime) {
                foreach ($event->getStations() as $station) {
                    $stationUid = (int)$station->getUid();

                    if ($stationUid <= 0) {
                        continue;
                    }

                    $runningNumber = $this->eventRepository->countByStationAndYearUntil(
                        $start,
                        $stationUid,
                        (int)$event->getUid()
                    );

                    $prefix = '';
                    if (method_exists($station, 'getPrefix')) {
                        $prefix = trim((string)$station->getPrefix());
                    }

                    $formattedNumber = $prefix !== ''
                        ? $prefix . '/' . str_pad((string)$runningNumber, 3, '0', STR_PAD_LEFT)
                        : str_pad((string)$runningNumber, 3, '0', STR_PAD_LEFT);

                    $stationNumbers[] = [
                        'station' => $station,
                        'stationUid' => $stationUid,
                        'stationName' => $station->getName(),
                        'prefix' => $prefix,
                        'runningNumber' => $runningNumber,
                        'formattedNumber' => $formattedNumber,
                        'year' => $start->format('Y'),
                    ];

                    if ($selectedStationUid > 0 && $stationUid === $selectedStationUid) {
                        $primaryNumber = $formattedNumber;
                        $primaryStationName = $station->getName();
                    }

                    if ($primaryNumber === '') {
                        $primaryNumber = $formattedNumber;
                        $primaryStationName = $station->getName();
                    }
                }
            }

            $items[] = [
                'event' => $event,
                'number' => $primaryNumber,
                'stationName' => $primaryStationName,
                'numbers' => $stationNumbers,
            ];
        }

        return $items;
    }

    /**
     * Gruppiert Fahrzeuge nach Feuerwehr und Standort
     */
    protected function groupVehiclesByBrigadeAndStation(Event $event): array
    {
        $grouped = [];
        $eventVehicles = $event->getVehicles()->toArray();

        foreach ($event->getStations() as $station) {
            $brigade = $station->getBrigade();
            $brigadeName = $brigade ? $brigade->getName() : 'Unbekannt';
            $brigadeSorting = ($brigade && method_exists($brigade, 'getSorting')) ? $brigade->getSorting() : 9999;
            $stationName = $station->getName();
            $stationSorting = method_exists($station, 'getSorting') ? $station->getSorting() : 9999;

            $vehicles = [];
            foreach ($station->getVehicles() as $vehicle) {
                if (in_array($vehicle, $eventVehicles, true)) {
                    $vehicles[] = $vehicle;
                }
            }

            if (!isset($grouped[$brigadeSorting])) {
                $grouped[$brigadeSorting] = [
                    'name' => $brigadeName,
                    'stations' => [],
                ];
            }

            $grouped[$brigadeSorting]['stations'][] = [
                'name' => $stationName,
                'sorting' => $stationSorting,
                'vehicles' => $vehicles,
            ];
        }

        ksort($grouped);

        foreach ($grouped as &$group) {
            if (isset($group['stations']) && is_array($group['stations'])) {
                usort(
                    $group['stations'],
                    static function (array $a, array $b): int {
                        return $a['sorting'] <=> $b['sorting'];
                    }
                );
            }
        }
        unset($group);

        return $grouped;
    }

    /**
     * Wandelt FlexForm-Datumswerte zuverlässig in DateTime um
     */
    protected function createDateTimeFromFlexFormValue($value): ?\DateTime
    {
        if ($value instanceof \DateTime) {
            return clone $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return new \DateTime($value->format('Y-m-d H:i:s'));
        }

        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        if (is_numeric($value)) {
            return (new \DateTime())->setTimestamp((int)$value);
        }

        if (is_string($value) && strtotime($value) !== false) {
            return new \DateTime($value);
        }

        return null;
    }

    /**
     * Normalisiert das Seitenfeld aus der FlexForm
     *
     * Kann je nach Konfiguration als int, String oder Array kommen.
     */
    protected function normalizeDetailPageUid($value): ?int
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (is_string($value) && strpos($value, ',') !== false) {
            $parts = explode(',', $value);
            $value = $parts[0] ?? null;
        }

        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return null;
        }

        return (int)$value;
    }

    /**
     * Normalisiert eine Datensatz-UID aus Request/FlexForm
     */
    protected function normalizeRecordUid($value): int
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        if (is_string($value) && strpos($value, ',') !== false) {
            $parts = explode(',', $value);
            $value = $parts[0] ?? null;
        }

        if (is_string($value) && strpos($value, '_') !== false) {
            $parts = explode('_', $value);
            $value = end($parts);
        }

        if ($value === null || $value === '' || $value === '0' || $value === 0) {
            return 0;
        }

        return (int)$value;
    }
}