/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

/**
 * @class HttpError
 * @property {string[]} messages
 * @property {{status: number}} response
 */
export default class HttpError extends Error {

  constructor(message, status) {
    super(message);

    this.name = 'HttpError';
    this.status = status;
    this.message = message;
  }

}
