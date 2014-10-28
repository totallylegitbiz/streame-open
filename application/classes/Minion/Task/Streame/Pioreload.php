<?

class Minion_Task_Streame_Pioreload extends Minion_Task {

    protected function _execute(array $params) {
            
      Logger::info("Creating users");
      foreach (Orm::user()->find_all() as $user) {
        Task::queue(function() use ($post) {
          Logger::info('New user: %o', $user->id);
          $user->_pio_create();
        });
      }
      
      Logger::info("Creating posts");
      foreach (Orm::site_post()->order_by('id', 'desc')->find_all() as $post) {
        Task::queue(function() use ($post) {
          Logger::info('New post: %o', $post->id);
          $post->_pio_create();
        });
      }
      
      Logger::info("Propagating actions");
      foreach (Orm::user_action()->find_all() as $action) {
        Task::queue(function() use ($action) {
          Logger::info('New action: %o', $action->id);
          $action->propagate();
        });
      }

    }
    
}