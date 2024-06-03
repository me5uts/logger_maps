<?php
declare(strict_types = 1);

/**
 * @package    μlogger
 * @author     Bartek Fabiszewski (www.fabiszewski.net)
 * @copyright  2017–2024 Bartek Fabiszewski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

namespace uLogger\Api;

use uLogger\Component\Auth;
use uLogger\Controller;
use uLogger\Entity\Config;


// Routes
/*
config
- R/A (require authorization)
- P/A (implies R/A, public access)

access types
- OPEN (!R/A)
- PUBLIC (R/A && P/A)
- PRIVATE (R/A && !P/A)

access levels
- ALL
- AUTHORIZED
- OWNER
- ADMIN

/config
✓ GET /config (get configuration; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
PUT /config (save configuration; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)

/session
✓ GET /session (get session data; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
✓ POST /session (log in; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
✓ DELETE /session (log out; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)

/users
✓ GET /users (get all users; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
GET /users/{id} (get user meta; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
✓ GET /users/{id}/tracks (get user tracks; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
✓ GET /users/{id}/position (get user last position; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
✓ GET /users/position (get all users last positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
PUT /users/{id} (update user; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
POST /users (new user; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)

/tracks
✓ GET /tracks/{id} (get track metadata; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
PUT /tracks/{id} (update track metadata; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
✓ GET /tracks/{id}/positions (track positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
✓ GET /tracks/{id}/positions?after={positionId} (track positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)

/positions
PUT /positions/{id} (update position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)

/locale
✓ GET /locales (list of languages, translated strings for current language; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
*/

trait Routes {
  /**
   * @param Auth $auth
   * @param Config $config
   * @return void
   */
  public function setupRoutes(Auth $auth, Config $config): void {

    $sessionController = new Controller\Session($auth, $config);
    $positionController = new Controller\Position($auth);
    $trackController = new Controller\Track($auth);
    $configController = new Controller\Config($config);
    $localeController = new Controller\Locale($config);
    $userController = new Controller\User($auth);


    // POST /session (log in; payload: {login, password}; access: OPEN-ALL, PUBLIC-ALL PRIVATE-ALL)
    $this->post('/api/session', function (string $login, string $password) use ($sessionController) { return $sessionController->logIn($login, $password); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_ALL ] ]);
    // DELETE /session (log out; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
    $this->delete('/api/session', function () use ($sessionController) { return $sessionController->logOut(); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_AUTHORIZED ] ]);
    // GET /session (get session data; access: OPEN-AUTHORIZED, PUBLIC-AUTHORIZED, PRIVATE-AUTHORIZED)
    $this->get('/api/session', function () use ($sessionController) { return $sessionController->check(); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_AUTHORIZED ] ]);

    // GET /config (get configuration; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
    $this->get('/api/config', function () use ($configController) { return $configController->get(); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_ALL ] ]);
    // PUT /config (save configuration; access: OPEN-ADMIN, PUBLIC-ADMIN, PRIVATE-ADMIN)
    $this->put('/api/config', function (array $config) use ($configController) { return $configController->save($config); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_ADMIN ] ]);

    // GET /locales (list of languages, translated strings for current language; access: OPEN-ALL, PUBLIC-ALL, PRIVATE-ALL)
    $this->get('/api/locales', function () use ($localeController) { return $localeController->get(); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_ALL ] ]);


    // PUT /positions/{id} (update position; access: OPEN-OWNER:ADMIN, PUBLIC-OWNER:ADMIN, PRIVATE-OWNER:ADMIN)
    $this->put('/api/positions/{id}', function (int $id) use ($positionController) { return $positionController->save($id); }, [ Auth::ACCESS_ALL => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ] ]);

    // GET /tracks/{id} (get track metadata; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
    $this->get('/api/tracks/{id}', function (int $id) use ($trackController) { return $trackController->get($id); }, [
      Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
      Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
      Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
    ]);
    // GET /tracks/{id}/positions[?afterId={afterId}] (track positions with optional filter; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
    $this->get('/api/tracks/{id}/positions', function (int $id, ?int $afterId = null) use ($positionController) { return $positionController->getAll($id, $afterId); }, [
      Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
      Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
      Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
    ]);

    // GET /users (get all users; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
    $this->get('/api/users', function () use ($userController) { return $userController->getAll(); }, [
      Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
      Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
      Auth::ACCESS_PRIVATE => [ Auth::ALLOW_ADMIN ]
    ]);
    // GET /users/{id}/tracks (get user tracks; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
    $this->get('/api/users/{id}/tracks', function (int $id) use ($userController) { return $userController->getTracks($id); }, [
      Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
      Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
      Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
    ]);
    // GET /users/{id}/position (get user last position; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-OWNER:ADMIN)
    $this->get('/api/users/{id}/position', function (int $id) use ($userController) { return $userController->getPosition($id); }, [
      Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
      Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
      Auth::ACCESS_PRIVATE => [ Auth::ALLOW_OWNER, Auth::ALLOW_ADMIN ]
    ]);
    // GET /users/position (get all users last positions; access: OPEN-ALL, PUBLIC-AUTHORIZED, PRIVATE-ADMIN)
    $this->get('/api/users/position', function () use ($userController) { return $userController->getAllPosition(); }, [
      Auth::ACCESS_OPEN => [ Auth::ALLOW_ALL ],
      Auth::ACCESS_PUBLIC => [ Auth::ALLOW_AUTHORIZED ],
      Auth::ACCESS_PRIVATE => [ Auth::ALLOW_ADMIN ]
    ]);

  }
}
