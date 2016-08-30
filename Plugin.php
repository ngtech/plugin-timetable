<?php

namespace Kanboard\Plugin\Timetable;

use DateTime;
use Kanboard\Core\Translator;
use Kanboard\Core\Plugin\Base;
use Kanboard\Core\Security\Role;

class Plugin extends Base
{
    public function initialize()
    {
        $container = $this->container;

        $this->applicationAccessMap->add('timetable', '*', Role::APP_ADMIN);
        $this->applicationAccessMap->add('timetableday', '*', Role::APP_ADMIN);
        $this->applicationAccessMap->add('timetableextra', '*', Role::APP_ADMIN);
        $this->applicationAccessMap->add('timetableoff', '*', Role::APP_ADMIN);
        $this->applicationAccessMap->add('timetableweek', '*', Role::APP_ADMIN);

        $this->template->hook->attach('template:user:sidebar:actions', 'timetable:user/sidebar');

        // Calculate time spent according to the timetable
        $this->hook->on('model:subtask-time-tracking:calculate:time-spent', function($user_id, DateTime $start, DateTime $end) use ($container) {
            $orig_duration   =  $container['timetable']->dateParser->getHours($start, $end);
            $ttable_duration =  $container['timetable']->calculateEffectiveDuration($user_id, $start, $end);
            
            if($ttable_duration <= $orig_duration){
                //Need to add overtime as needed
                $overtime_hours = $container['timetable']->calculateAditionalOverTime($user_id, $start, $end);                
                return $orig_duration;
            }
            
            return $ttable_duration;
        });

        // Split calendar events according to the timetable
        $this->hook->on('model:subtask-time-tracking:calendar:events', function($user_id, array $events, $start, $end) use ($container) {
            return $container['timetable']->calculateEventsIntersect($user_id, $events, $start, $end);
        });
    }

    public function onStartup()
    {
        Translator::load($this->languageModel->getCurrentLanguage(), __DIR__.'/Locale');
    }

    public function getClasses()
    {
        return array(
            'Plugin\Timetable\Model' => array(
                'Timetable',
                'TimetableDay',
                'TimetableExtra',
                'TimetableWeek',
                'TimetableOff',
            )
        );
    }

    public function getPluginName()
    {
        return 'Timetable';
    }

    public function getPluginDescription()
    {
        return t('Timetable management for users - Modified to Automatically track  / add overtime');
    }

    public function getPluginAuthor()
    {
        return 'Frédéric Guillot';
    }

    public function getPluginVersion()
    {
        return '1.0.8.m';
    }

    public function getPluginHomepage()
    {
        return 'https://github.com/ngtech/plugin-timetable';
    }
}
