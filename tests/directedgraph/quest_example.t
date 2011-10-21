#!/usr/bin/env php
<?php

include_once __DIR__ . '/../common.php';
use Gaia\Test\Tap;
use Gaia\DirectedGraph\Node;
use Gaia\DirectedGraph\Execution;

Tap::plan(16);

class BattleQuest {
    public static function start(){}
    public static function meetTroll(){}
    public static function meetDragon(){}
    public static function slayTroll(){}
    public static function stunned(){}
    public static function findLoot(){}
    public static function findGold(){}
    public static function finish(){}    
}

class QuestAction {
    public static function rechargeHealth( Execution $e ){ $e->data->health = 10; }
    public static function loseHealth( Execution $e ){ $e->data->health = 0; }
    public static function useHealth( Execution $e ){ if( $e->data->health < 1) return $e->event('return home'); $e->data->health = $e->data->health - 1; }
    public static function gainExperience( Execution $e ){ $e->data->exp = $e->data->exp + 1; }
}

$start = new Node('BattleQuest::Start');
$meet_troll = new Node('BattleQuest::MeetTroll'); 
$slay_troll = new Node('BattleQuest::SlayTroll');
$stunned = new Node('BattleQuest::Stunned');
$meet_dragon = new Node('BattleQuest::MeetDragon');
$slay_dragon = new Node('BattleQuest::SlayDragon');
$find_loot = new Node('BattleQuest::FindLoot');
$find_gold = new Node('BattleQuest::FindGold');
$finish = new Node('BattleQuest::finish');

$start->add('enter-node', 'QuestAction::rechargeHealth');
$start->add('enter valley', $meet_troll);
$start->add('enter mountains', $meet_dragon);

$meet_troll->add('leave-node', 'QuestAction::gainExperience');
$meet_troll->add('return home', $start);
$meet_troll->add('swing sword', 'QuestAction::useHealth');
$meet_troll->add('swing sword', $slay_troll);
$meet_troll->add('cast spell', 'QuestAction::useHealth');
$meet_troll->add('cast spell', $slay_troll);
$meet_troll->add('say hello', $stunned);
$meet_troll->add('do nothing', $stunned );
$meet_troll->add('give backrub', $find_loot );
$meet_troll->add('enter mountains',  $meet_dragon);

$slay_troll->add('enter-node', 'QuestAction::gainExperience');
$slay_troll->add('enter mountains', $meet_dragon );
$slay_troll->add('sit down', $find_loot );
$slay_troll->add('wander', $meet_troll );
$find_gold->add('do nothing', $meet_troll );


$find_loot->add('enter mountains', $meet_dragon );
$find_loot->add('wander', $meet_troll );

$stunned->add('enter-node', 'QuestAction::loseHealth');
$stunned->add('return home', $start );

$meet_dragon->add('enter-node', 'QuestAction::useHealth');
$meet_dragon->add('swing sword', $stunned );
$meet_dragon->add('cast spell', $stunned );
$meet_dragon->add('throw grenade', $slay_dragon );
$meet_dragon->add('sit down', $stunned );
$meet_dragon->add('offer cookie', $find_gold );
$meet_dragon->add('give backrub', $stunned );


$find_gold->add('give to stranger', $finish );
$find_gold->add('do nothing', $meet_dragon );
$find_gold->add('bury gold', $meet_dragon );

$slay_dragon->add('enter-node', 'QuestAction::gainExperience');
$slay_dragon->add('return home', $start);
$slay_dragon->add('wander', $meet_dragon );

$finish->add('enter-node', 'QuestAction::gainExperience');




$execution = new Execution($start);

$execution->event('enter valley');
Tap::ok($meet_troll === $execution->node, 'enter valley, meet troll');
$execution->event('say hello');
Tap::ok($stunned === $execution->node, 'say hello, get stunned');
$execution->event('return home');
Tap::ok($start === $execution->node, 'return home, back at start');
$execution->event('enter valley');
Tap::ok($meet_troll === $execution->node, 'enter valley, meet troll');
$execution->event('swing sword');
Tap::ok($slay_troll === $execution->node, 'swing sword, slay troll');
$execution->event('return home');
Tap::ok($slay_troll === $execution->node, 'return home, back at start');
$execution->event('enter valley');
$execution->event('cast spell');
Tap::ok($slay_troll === $execution->node, 'enter valley, meet troll, and cast spell, slay troll');
$execution->event('enter mountains');
Tap::ok($meet_dragon === $execution->node, 'enter mountains, meet dragon');
$execution->event('throw grenade');
Tap::ok($slay_dragon === $execution->node, 'thow grenade, slay dragon');
$execution->event('wander');
Tap::ok($meet_dragon === $execution->node, 'wander around, run into another dragon');
$execution->event('cast spell');
$execution->event('return home');
Tap::ok($start === $execution->node, 'cast spell, get stunned, go home');
$execution->event('enter mountains');
$execution->event('offer cookie');
Tap::ok($find_gold === $execution->node, 'enter the mountains and offer dragon a cookie, find gold');
$execution->event('bury gold');
Tap::ok($meet_dragon === $execution->node, 'bury the gold, run into another dragon');
$execution->event('offer cookie');
$execution->event('give to stranger');
Tap::ok($finish === $execution->node, 'buy off the dragon with a cookie, find gold, and give to a stranger, complete quest');
Tap::ok($execution->data->exp == 5, 'after all this, exp is 5');
Tap::ok($execution->data->health == 8, 'health is 8');