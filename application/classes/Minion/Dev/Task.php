<?

abstract class Minion_Dev_Task extends Minion_Task {
  static function is_enabled() {
    return IS_DEV;
  }
}