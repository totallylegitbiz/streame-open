<?

class Minion_Task_Test_Gearman extends Minion_Task {
  protected function _execute(array $params) {
    
    $f = function($priority) {
      Logger::info("================= RAN PRIORITY %o =================", $priority);
/*       sleep(1); */
    };
    $i=0;
    Task::queue($f, ['LOW ' .    $i++],    Task::PRIORITY_LOW);
    Task::queue($f, ['NORMAL ' . $i++], Task::PRIORITY_NORMAL);
    Task::queue($f, ['HIGH ' .   $i++],   Task::PRIORITY_HIGH);
    Task::queue($f, ['HIGH ' .   $i++],   Task::PRIORITY_HIGH);
    Task::queue($f, ['NORMAL ' . $i++], Task::PRIORITY_NORMAL);
    Task::queue($f, ['LOW ' .    $i++],    Task::PRIORITY_LOW);
    Task::queue($f, ['LOW ' .    $i++],    Task::PRIORITY_LOW);
    Task::queue($f, ['NORMAL ' . $i++], Task::PRIORITY_NORMAL);
    Task::queue($f, ['HIGH ' .   $i++],   Task::PRIORITY_HIGH);
    Task::queue($f, ['HIGH ' .   $i++],   Task::PRIORITY_HIGH);
    Task::queue($f, ['NORMAL ' . $i++], Task::PRIORITY_NORMAL);
    Task::queue($f, ['LOW ' .    $i++],    Task::PRIORITY_LOW);
    
/*
    $f = function() {
      Logger::debug("I am going to error out");
      $nonexistant->error();
    };
    for($i=0;$i<20;$i++) {
      Task::queue($f, ['LOW'],    Task::PRIORITY_LOW);
    }
*/
  }
}