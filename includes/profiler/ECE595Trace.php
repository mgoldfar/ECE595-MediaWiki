<?php

class ECE595Trace extends ProfilerSimple {
	var $trace = "Beginning trace: \n";
	var $trace_array = array();
	var $memory = 0;
	
	function profileIn( $functionname ) {
		parent::profileIn( $functionname );
	
		$item = array(true, $functionname, $this->memoryDiff(), count($this->mWorkStack), 0.0);
		array_push($this->trace_array, $item);	
	}

	function profileOut($functionname) {
		global $wgDebugFunctionEntry;

		if ( $wgDebugFunctionEntry ) {
			$this->debug(str_repeat(' ', count($this->mWorkStack) - 1).'Exiting '.$functionname."\n");
		}

		list( $ofname, /* $ocount */ , $ortime ) = array_pop( $this->mWorkStack );

		if ( !$ofname ) {
			$this->trace .= "Profiling error: $functionname\n";
		} else {
			if ( $functionname == 'close' ) {
				$message = "Profile section ended by close(): {$ofname}";
				$functionname = $ofname;
				$this->trace .= $message . "\n";
			}
			elseif ( $ofname != $functionname ) {
				$this->trace .= "Profiling error: in({$ofname}), out($functionname)";
			}
			
			$elapsedreal = $this->getTime() - $ortime;
			$item = array(false, $functionname, $this->memoryDiff(), count($this->mWorkStack), $elapsedreal);
			array_push($this->trace_array, $item);
		}
	}

	function memoryDiff() {
		$diff = memory_get_usage() - $this->memory;
		$this->memory = memory_get_usage();
		return $diff / 1024;
	}

	function logData() {
		// collapse any traces that are immediate duplicates:
		// > func   -->    + func
		// < func
		
		$total_time = 0.0;
		for($i = 0; $i < count($this->trace_array); $i++) {
			// if the function called immediatly after is the same name then it can be collapsed
			// corner case: recursive calls, if both entries are "OPEN" then do not collapse
			$trace = $this->trace_array[$i];
			$collapse = false;
			$next_trace = NULL;
			if($i + 1 < count($this->trace_array)) {
				$next_trace = $this->trace_array[$i + 1];
				if($trace[0] != $next_trace[0] && $trace[1] == $next_trace[1]) {
					$collapse = true;
				}
			}
			
			if($collapse) {
				$this->trace .= sprintf("%f %f + %s\n", $next_trace[4], $next_trace[2] + $trace[2], $trace[1]);
				$total_time += $next_trace[4];
				// skip next item
				$i += 1;
				continue;
			} else {
				if($trace[0]) {
					// open
					$this->trace .= sprintf("- %f > %s\n", $trace[2], $trace[1]);
				} else {
					// close
					$this->trace .= sprintf("%f %f < %s\n", $trace[4], $trace[2], $trace[1]);
					$total_time += $trace[4];
				}
			}
		}
		
		print "<!-- \n {$this->trace} \n -->\n";
		print "<!-- Total Execution Time: $total_time -->\n";
		
		// send to memcached client:
		//require_once( "./includes/objectcache/MemcachedClient.php" );
		$start = $this->getTime();
		$mc = new MWMemcached(array("servers"=>array("localhost:11211")));
		$mc->set("trace", $this->trace);

		$total = ($this->getTime() - $start);
		print "<!-- Trace Logging Time: $total -->\n";
	}
}
