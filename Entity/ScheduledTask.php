<?php

    namespace Goksagun\SchedulerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Goksagun\SchedulerBundle\Enum\StatusInterface;
use Goksagun\SchedulerBundle\Utils\ArrayHelper;
use Goksagun\SchedulerBundle\Utils\DateHelper;
use Goksagun\SchedulerBundle\Utils\HashHelper;

/**
 * ScheduledTask
 *
 * @ORM\Table(
 *     name="scheduled_tasks",
 *     indexes={
 *         @ORM\Index(name="status_idx", columns={"status"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ScheduledTask implements StatusInterface
{
    const EXCLUDED_PROPS = ['createdAt', 'updatedAt'];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="string")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="Goksagun\SchedulerBundle\Doctrine\ORM\Id\HashIdGenerator")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=2048, options={"comment":"Scheduled task command name"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="expression", type="string", length=255, nullable=true, options={"comment":"Scheduled task expression"})
     */
    private $expression;

    /**
     * @var string
     *
     * @ORM\Column(name="times", type="smallint", nullable=true, options={"comment":"Scheduled task execution count"})
     */
    private $times;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start", type="datetime", nullable=true, options={"comment":"Scheduled task start date and time"})
     */
    private $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stop", type="datetime", nullable=true, options={"comment":"Scheduled task stop date and time"})
     */
    private $stop;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, options={"default":"active", "comment":"Scheduled task status"})
     */
    private $status;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", options={"comment":"Scheduled task creation time"})
     */
    private $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=true, options={"comment":"Scheduled task update time"})
     */
    private $updatedAt;


    /**
     * ScheduledTask constructor.
     */
    public function __construct()
    {
        $this->status = static::STATUS_ACTIVE;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->createdAt = DateHelper::date();
    }

    /**
     * @ORM\PreUpdate()
     */
    public function preUpdate()
    {
        $this->id = HashHelper::generateIdFromProps(
            ArrayHelper::only(get_object_vars($this), HashHelper::GENERATED_PROPS)
        );

        $this->updatedAt = DateHelper::date();
    }

    /**
     * @param array $props
     * @return array
     */
    public function toArray(array $props = [])
    {
        $allProps = get_object_vars($this);

        return empty($props)
            ? ArrayHelper::except($allProps, static::EXCLUDED_PROPS)
            : ArrayHelper::only($allProps, $props);
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ScheduledTask
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set expression.
     *
     * @param string|null $expression
     *
     * @return ScheduledTask
     */
    public function setExpression($expression = null)
    {
        $this->expression = $expression;

        return $this;
    }

    /**
     * Get expression.
     *
     * @return string|null
     */
    public function getExpression()
    {
        return $this->expression;
    }

    /**
     * Set times.
     *
     * @param int|null $times
     *
     * @return ScheduledTask
     */
    public function setTimes($times = null)
    {
        $this->times = $times;

        return $this;
    }

    /**
     * Get times.
     *
     * @return int|null
     */
    public function getTimes()
    {
        return $this->times;
    }

    /**
     * Set start.
     *
     * @param \DateTime|null $start
     *
     * @return ScheduledTask
     */
    public function setStart($start = null)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start.
     *
     * @return \DateTime|null
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Set stop.
     *
     * @param \DateTime|null $stop
     *
     * @return ScheduledTask
     */
    public function setStop($stop = null)
    {
        $this->stop = $stop;

        return $this;
    }

    /**
     * Get stop.
     *
     * @return \DateTime|null
     */
    public function getStop()
    {
        return $this->stop;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return ScheduledTask
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return ScheduledTask
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return ScheduledTask
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
