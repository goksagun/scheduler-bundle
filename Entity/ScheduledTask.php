<?php

namespace Goksagun\SchedulerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ScheduledTask
 *
 * @ORM\Table(
 *     name="scheduled_tasks",
 *     indexes={
 *         @ORM\Index(name="name_idx", columns={"name"}),
 *         @ORM\Index(name="search_idx", columns={"name", "status", "created_at"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Goksagun\SchedulerBundle\Repository\ScheduledTaskRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class ScheduledTask
{
    const STATUS_QUEUED = 'queued';
    const STATUS_EXECUTED = 'executed';
    const STATUS_FAILED = 'failed';

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, options={"comment":"Scheduled task command name"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, options={"default":"queued", "comment":"Scheduled task status"})
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255, nullable=true, options={"comment":"Scheduled task message"})
     */
    private $message;

    /**
     * @var string
     *
     * @ORM\Column(name="output", type="text", nullable=true, options={"comment":"Scheduled task output message"})
     */
    private $output;

    /**
     * @var int
     *
     * @ORM\Column(name="remaining", type="smallint", nullable=true, options={"comment":"Scheduled task remaining execution"})
     */
    private $remaining;

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
        $this->status = static::STATUS_QUEUED;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateTimestamps()
    {
        if (null == $this->createdAt) {
            $this->createdAt = new \DateTime();
        } else {
            $this->updatedAt = new \DateTime();
        }
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
     * Is task executed
     *
     * @return bool
     */
    public function isExecuted()
    {
        return self::STATUS_EXECUTED === $this->status;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return ScheduledTask
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set output
     *
     * @param string $output
     *
     * @return ScheduledTask
     */
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Get output
     *
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Set remaining
     *
     * @param int $remaining
     *
     * @return ScheduledTask
     */
    public function setRemaining($remaining)
    {
        $this->remaining = $remaining;

        return $this;
    }

    /**
     * Get remaining
     *
     * @return int
     */
    public function getRemaining()
    {
        return $this->remaining;
    }

    /**
     * Decrease remaining
     *
     * @return ScheduledTask
     */
    public function decreaseRemaining()
    {
        --$this->remaining;

        return $this;
    }

    /**
     * Is remaining zero
     *
     * @return bool
     */
    public function isRemainingZero()
    {
        return 0 === $this->remaining;
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
