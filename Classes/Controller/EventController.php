<?php
namespace In2code\RescueReports\Controller;

use In2code\RescueReports\Domain\Model\Event;
use In2code\RescueReports\Domain\Repository\EventRepository;
use In2code\RescueReports\Domain\Repository\TypeRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class EventController extends ActionController
{
    protected EventRepository $eventRepository;
    protected TypeRepository $typeRepository;

    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function injectTypeRepository(TypeRepository $typeRepository): void
    {
        $this->typeRepository = $typeRepository;
    }

    /**
     * Liste aller Einsätze (mit optionalen FlexForm-Filtern)
     */
    public function listAction(string $searchWord = null): ResponseInterface
    {
        $maxCount = (int)($this->settings['maxCount'] ?? 0);
        $dateFromValue = $this->settings['dateFrom'] ?? null;
        $dateToValue = $this->settings['dateTo'] ?? null;
        $enableSearch = (bool)($this->settings['enableSearch'] ?? false);
        $templateVariant = (string)($this->settings['templateVariant'] ?? 'standard');
        $detailPageUid = $this->normalizeDetailPageUid($this->settings['detailPageUid'] ?? null);

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

        if ($enableSearch && $searchWord !== '') {
            $events = $this->eventRepository->search($searchWord, $dateFrom, $dateTo, $maxCount);
        } else {
            $events = $this->eventRepository->findFiltered($dateFrom, $dateTo, $maxCount);
        }

        $this->view->assignMultiple([
            'events' => $events,
            'searchWord' => $searchWord,
            'enableSearch' => $enableSearch,
            'maxCount' => $maxCount,
            'dateFrom' => $this->createDateTimeFromFlexFormValue($dateFromValue),
            'dateTo' => $this->createDateTimeFromFlexFormValue($dateToValue),
            'templateVariant' => $templateVariant,
            'detailPageUid' => $detailPageUid,
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
            'templateVariant' => (string)($this->settings['templateVariant'] ?? 'standard'),
            'settings' => $this->settings,
        ]);

        return $this->htmlResponse();
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
            $brigadePriority = ($brigade && method_exists($brigade, 'getPriority')) ? $brigade->getPriority() : 9999;
            $stationName = $station->getName();
            $stationSorting = method_exists($station, 'getSorting') ? $station->getSorting() : 9999;

            $vehicles = [];
            foreach ($station->getVehicles() as $vehicle) {
                if (in_array($vehicle, $eventVehicles, true)) {
                    $vehicles[] = $vehicle;
                }
            }

            if (!isset($grouped[$brigadePriority])) {
                $grouped[$brigadePriority] = [
                    'name' => $brigadeName,
                    'stations' => [],
                ];
            }

            $grouped[$brigadePriority]['stations'][] = [
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
}