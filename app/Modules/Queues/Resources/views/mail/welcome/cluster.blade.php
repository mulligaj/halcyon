@php
$resources = array();
$frontends = array();
foreach ($activity as $resourceid => $data):
	$resources[] = $data->resource->name;
	$frontends[] = $data->resource->rolename . '.' . request()->getHttpHost();
endforeach;
@endphp

@component('mail::message')
Hello {{ $user->name }},

Your account on {{ implode(', ', $resources) }} has been created and are ready for use. Details about using these and other {{ config('app.name') }} resources are included below.

---

**Work is done on the clusters by running jobs through Slurm.** Further information about Slurm and running jobs can be found in the [user guides](https://www.rcac.purdue.edu/knowledge/). Informal, one-on-one help is available from Research Computing staff at **[Coffee Break Consultations](https://www.rcac.purdue.edu/coffee)**, weekly gatherings held at coffee shops around campus. Check the [Coffee Break schedule](https://www.rcac.purdue.edu/coffee) each week for locations and times.

**You can access the clusters through the front-ends ({{ implode(', ', $frontends) }}) with your Purdue Career Account credentials using [SSH](https://www.rcac.purdue.edu/knowledge/) or [Thinlinc](https://www.rcac.purdue.edu/knowledge/)**. You've been granted access to the following queues:

@php
$partner = false;
@endphp
@foreach ($activity as $resourceid => $data)
### {{ $data->resource->name }}:

@foreach ($data->queues as $queue)
* {{ $queue->name }} - {{ $queue->cores }} cores, {{ $queue->walltime }} hours
@endforeach
@foreach ($data->standbys as $standby)
@php
if (preg_match("/^partner/", $standby->name))
{
	$partner = true;
}
@endphp
* {{ $standby->name }} - {{ $standby->walltime }} hours
@endforeach
@endforeach

You can also see this list by running the `slist` command.

@if ($partner)
One of the above resources provides partners and their researchers who have purchased shared access to the cluster through a shared 'partner' queue. If your research group has purchased dedicated access, there will also be a queue named after that partner or research group on this resource.
@endif

You also have access to the "standby" queue. This queue utilizes idle cores from other queues. You can use this queue to run jobs of up to 4 hours. Wait times in standby will vary wildly (minutes to days) depending on cluster utilization and how many nodes your jobs request.

----

You have a home directory that is shared across all ITaP Research Computing resources. This space has a quota of 25GB.

Scratch space is also available for storing large input and output data during computations. This space offers both a much larger quota and better performance than your home directory. **There is no backup service for scratch directories and files not accessed or modified in the [last 60 days will be removed](https://www.rcac.purdue.edu/policies/scratchpurge/). Files in scratch directories are not recoverable if they are purged or accidentally deleted.** You will receive a warning email one week in advance of files being purged as a reminder to back up files. This space has the following quotas:

@foreach ($activity as $resourceid => $data)
@if ($data->storage)
* {{ $data->resource->name }}: {{ $data->storage->space }} space; {{ $data->storage->files }} files
@endif
@endforeach

You can also see this list with the `myquota` command.

Long-term archival space is also offered via the Fortress HPSS Archival system. Fortress stores files on a tape library and uses a tape robot to retrieve and store these files upon request. Further information on using Fortress can be found in the [user guide](https://www.rcac.purdue.edu/storage/fortress/guide/).

----

Please also review the [acceptable use]({{ route('page', ['uri' => 'policies/resourceuse']) }}), [data]({{ route('page', ['uri' => 'policies/dataaccess']) }}), [quota]({{ route('page', ['uri' => 'policies/defaultquotas']) }}), [scratch purge policies]({{ route('page', ['uri' => 'policies/scratchpurge']) }}), and [other policies]({{ route('page', ['uri' => 'policies']) }}).
@endcomponent