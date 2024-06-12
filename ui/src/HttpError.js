/*
 * μlogger
 *
 * Copyright(C) 2024 Bartek Fabiszewski (www.fabiszewski.net)
 *
 * This is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, see <http://www.gnu.org/licenses/>.
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
