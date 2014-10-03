***This project is deprecated use the official [Phing JsHint Task](http://www.phing.info/docs/stable/hlhtml/index.html#JsHintTask)***

# JSHint Phing Task

This project is a [Phing](http://phing.info) build tool task for running [node-jshint](https://github.com/jshint/node-jshint)

## Example

To use this task, add the classpath where you placed the JsHintTask.php in your build.xml file:

	<path id="project.class.path">
		<pathelement dir="dir/to/jshinttaskfile/"/>
	</path>

Then include it with a taskdef tag in your build.xml file:

	<taskdef name="jshint" classname="JsHintTask">
		<classpath refid="project.class.path"/>
	</taskdef>


You can now use the task

	<target name="jshint" description="Javascript Lint">
		<jshint haltonfailure="true" config="${basedir}/jshint-config.json">
			<fileset dir="${basedir}/js">
				<include name="**/*.js"/>
			</fileset>
		</jshint>
	</target>

## Task Attributes

#### Required
_There are no required attributes._

#### Optional
 - **config** - Specifies the jshint config file.
 - **executable** - Path to jshint command.
 - **haltonfailure** - If the build should fail if any lint warnings is found.
 - **cachefile** - puts last-modified times to a file and does not check them if not changed

