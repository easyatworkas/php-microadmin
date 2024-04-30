<?php

/*
 * [x] Refactor
 * [x] Reformat
 * [x] Complete
 */

namespace Ext\Traits;

use Ext\Models\CalendarEvent;
use Ext\Models\Customer;
use Ext\Models\CustomerCalendarEvent;

trait HasCalendarEvents
{
    private function calendarEventClass(): string
    {
        return match (getClass($this)) {
            Customer::class => CustomerCalendarEvent::class,
            default => CalendarEvent::class
        };
    }

    public function calendarEvents(): array
    {
        if (!$this->hasProduct('Calendar')) return [];

        return static::newQuery($this->getFullPath() . '/calendar_events')
            ->setModel($this->calendarEventClass())
            ->getAll()
            ->all();
    }

    public function getCalendarEvent(string $key): ?CalendarEvent
    {
        $calendarEvent = null;
        try {
            $calendarEvent = static::newQuery($this->getFullPath() . "/calendar_events/$key")
                ->setModel($this->calendarEventClass())
                ->get();
        } catch (\Exception $e) {
            // TODO: Log error
        }
        return $calendarEvent;
    }

    public function findCalendarEvent(array $attributes, string $when = 'now'): ?CalendarEvent
    {
        foreach ($this->calendarEvents() as $model) {
            if ($model->matches($attributes)) {
                if (dateTimeWithin($when, $model->from, $model->to)) {
                    return $model;
                }
            }
        }
        return null;
    }

    public function findCalendarEvents(array $attributes, string $from = null, string $to = null, bool $strict = false): array
    {
        // If no params provided, return all
        if (empty($attributes) && is_null($from) && is_null($to)) {
            return $this->calendarEvents();
        }

        $found = [];
        $start = is_null($from) ? $this->created_at : $from;
        $end = $to;

        foreach ($this->calendarEvents() as $model) {
            if ($model->matches($attributes)) {
                if (intervalWithin($model->from, $model->to, $start, $end,
                    $strict)) {
                    $found[] = $model;
                }
            }
        }
        return $found;
    }

    public function hasCalendarEvent(array $attributes, string $when = 'now'): bool
    {
        return (bool)$this->findCalendarEvent($attributes, $when);
    }

    public function addCalendarEvent(string $name, string $description, string $from, string $to, string $color = 'random'): ?CalendarEvent
    {
        if (!$this->hasProduct('Calendar')) return null;

        $newColor = $color == 'random' ? '#' . substr(md5(mt_rand()), 0, 6) : $color;

        // Create new CalendarEvent
        $newEvent = CalendarEvent::newInstance([
            'name' => $name,
            'description' => $description,
            'from' => $from,
            'to' => $to,
            'color' => $newColor,
        ])->setPath($this->getFullPath() . '/calendar_events');

        $newEvent->save();

        return $newEvent;
    }

    public function copyCalendarEvent(CalendarEvent $source): ?CalendarEvent
    {
        return $this->addCalendarEvent($source->name, $source->description, $source->from, $source->color);
    }
}