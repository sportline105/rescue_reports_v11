<?php
namespace In2code\RescueReports\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class Event extends AbstractEntity
{
    protected string $title = '';
    protected string $description = '';
    protected ?\DateTime $start = null;
    protected ?\DateTime $end = null;
    protected string $number = '';
    protected string $location = '';

    /**
    * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\In2code\RescueReports\Domain\Model\Vehicle>
    */
    protected ObjectStorage $vehicles;


    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\In2code\RescueReports\Domain\Model\Station>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     * @TYPO3\CMS\Extbase\Annotation\ORM\Lazy
     */
    protected ObjectStorage $stations;

    /** @var ObjectStorage<FileReference> */
    protected ObjectStorage $images;

    /** @var ObjectStorage<Type> */
    protected ObjectStorage $types;

    public function __construct()
    {
        $this->vehicles = new ObjectStorage();
        $this->stations = new ObjectStorage();
        $this->images = new ObjectStorage();
        $this->types = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getStart(): ?\DateTime
    {
        return $this->start;
    }

    public function setStart(?\DateTime $start): void
    {
        $this->start = $start;
    }

    public function getEnd(): ?\DateTime
    {
        return $this->end;
    }

    public function setEnd(?\DateTime $end): void
    {
        $this->end = $end;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function getNumberShort(): string
    {
        $number = (string)$this->number;
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return substr(str_pad($digits, 3, '0', STR_PAD_LEFT), -3);
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    /** @return ObjectStorage<Vehicle> */
    public function getVehicles(): ObjectStorage
    {
        return $this->vehicles;
    }

    public function setVehicles(ObjectStorage $vehicles): void
    {
        $this->vehicles = $vehicles;
    }

    public function addVehicle(Vehicle $vehicle): void
    {
        $this->vehicles->attach($vehicle);
    }

    public function removeVehicle(Vehicle $vehicle): void
    {
        $this->vehicles->detach($vehicle);
    }

    /**
     * @return ObjectStorage<Station>
     */
    public function getStations(): ObjectStorage
    {
        return $this->stations;
    }

    public function setStations(ObjectStorage $stations): void
    {
        $this->stations = $stations;
    }

    public function addStation(Station $station): void
    {
        $this->stations->attach($station);
    }

    public function removeStation(Station $station): void
    {
        $this->stations->detach($station);
    }

    /** @return ObjectStorage<FileReference> */
    public function getImages(): ObjectStorage
    {
        return $this->images;
    }

    public function setImages(ObjectStorage $images): void
    {
        $this->images = $images;
    }

    public function addImage(FileReference $image): void
    {
        $this->images->attach($image);
    }

    public function removeImage(FileReference $image): void
    {
        $this->images->detach($image);
    }

    /** @return ObjectStorage<Type> */
    public function getTypes(): ObjectStorage
    {
        return $this->types;
    }

    public function setTypes(ObjectStorage $types): void
    {
        $this->types = $types;
    }

    public function addType(Type $type): void
    {
        $this->types->attach($type);
    }

    public function removeType(Type $type): void
    {
        $this->types->detach($type);
    }

    public function getDuration(): string
    {
        if (!$this->start instanceof \DateTimeInterface || !$this->end instanceof \DateTimeInterface) {
            return '';
        }

        $diff = $this->start->diff($this->end);

        $hours = ($diff->days * 24) + $diff->h;
        $minutes = $diff->i;

        if ($hours > 0) {
            return sprintf('%d Std. %02d Min.', $hours, $minutes);
        }

        return sprintf('%d Min.', $minutes);
    }
}