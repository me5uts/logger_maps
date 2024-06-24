/**
 * @package    μlogger
 * @copyright  2017–2024 Bartek Fabiszewski (www.fabiszewski.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL version 3 or later
 */

const exec = require('child_process').execSync;

const jsSourcesModified = () => {
  const lastBuildCommit = exec('git log -1 --pretty="format:%H" ui/dist/*bundle*js*').toString();
  const output = exec(`git diff --name-only ${lastBuildCommit} HEAD ui/js/src`).toString();
  return !!output && output.split('\n').length > 0;
};

const cssSourcesModified = () => {
  const lastBuildCommit = exec('git log -1 --pretty="format:%H" ui/dist/*.css*').toString();
  const output = exec(`git diff --name-only ${lastBuildCommit} HEAD ui/src/assets/css`).toString();
  return !!output && output.split('\n').length > 0;
};

if (jsSourcesModified() || cssSourcesModified()) {
  console.log('\nPlease update and commit distribution bundle first!\nYou may still push using --no-verify option.\n');
  process.exitCode = 1;
}
