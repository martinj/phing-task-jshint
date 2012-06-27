<?php  
/*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
* "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
* LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
* A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
* OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
* SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
* LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
* DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
* THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
* (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
* OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
require_once 'phing/Task.php';
require_once 'phing/util/DataStore.php';
/**
* A Javascript lint task using JSHint (https://github.com/jshint/node-jshint)
* This class is based on Stefan Priebsch JslintTask
* 
* @author Martin Jonsson <martin.jonsson@gmail.com>
* @version 1.0.1
*/
class JsHintTask extends Task {
	private $file = null;
	private $config = null;
	private $cache = null;
	private $haltOnFailure = false;    
	private $hasErrors = false;
	private $filesets = array();
    private $executable = 'jshint';

	public function setFile(PhingFile $file) {
		$this->file = $file;
	}

	public function setExecutable($executable) {
		$this->executable = $executable;
	}
	
	public function setConfig(PhingFile $config) {
		$this->config = $config;
	}
	
	public function setCacheFile(PhingFile $file) {
		$this->cache = new DataStore($file);
	}
	
	public function setHaltOnFailure($haltOnFailure) {
		$this->haltOnFailure = $haltOnFailure;
	}
    
	/**
	* Create fileset for this task
	*/
	public function createFileSet() {
		$num = array_push($this->filesets, new FileSet());
		return $this->filesets[$num-1];
	}
  
	public function main() {
		$this->hasErrors = false;
		
		if(!isset($this->file) and count($this->filesets) == 0) {
			throw new BuildException("Missing either a nested fileset or attribute 'file' set");
		}

		exec($this->executable . ' -v', $output, $ret);
		if ($ret !== 0) {
			throw new BuildException('JSHint command not found');
		}
    
		if($this->file instanceof PhingFile) {
			$this->lint($this->file->getPath());
		} else { // process filesets
			$project = $this->getProject();
		
			foreach($this->filesets as $fs) {
				$ds = $fs->getDirectoryScanner($project);
				$files = $ds->getIncludedFiles();
				$dir = $fs->getDir($this->project)->getPath();
				
				foreach($files as $file) {
					$this->lint($dir.DIRECTORY_SEPARATOR.$file);
				}
			}
		}
  
		if ($this->haltOnFailure && $this->hasErrors) throw new BuildException('Syntax error(s) in JS files');
    }
  
	
	public function lint($file) {
		$command = $this->executable . ' "' . $file . '" ';
		if (isset($this->config)) {
			$command .= '--config ' . escapeshellarg($this->config->getPath()) . ' ';
		}

		if(!file_exists($file)) {
			throw new BuildException('File not found: ' . $file);
		}
		
		if(!is_readable($file))	{
			throw new BuildException('Permission denied: ' . $file);
		}
		
		if ($this->cache){
			$lastmtime = $this->cache->get($file);
			if ($lastmtime >= filemtime($file)) {
				$this->log("Not linting '" . $file . "' due to cache", Project::MSG_DEBUG);
				return false;
			}
		}

		$messages = array();
		exec($command, $messages);		
		$summary = $messages[sizeof($messages) - 1];

		if (preg_match('/(\d+)\serror/', $summary, $matches)) {
			$errorCount = $matches[1];
			$this->hasErrors = true;
			$this->log($file . ': ' . $errorCount . ' errors detected', Project::MSG_ERR);
			
			foreach ($messages as $message) {
				$matches = array();
				if (preg_match('/^([^:]+):\sline\s([^,]+),\scol\s([^,]+),([^\.]+)\.$/', $message, $matches)) {
					$error = array('filename' => $matches[1], 'line' => $matches[2], 'column' => $matches[3], 'message' => $matches[4]);
					$this->log('- line ' . $error['line'] . (isset($error['column']) ? ' column ' . $error['column'] : '') . ': ' . $error['message'], Project::MSG_ERR);
				}
			}
		} else {
			if ($this->cache) {
				$this->cache->put($file, filemtime($file));
			}				
		}
	}
	
}