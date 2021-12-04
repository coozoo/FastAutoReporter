#!/usr/bin/perl

require LWP::UserAgent;
use Data::UUID;
use Cwd;
use File::Basename qw( dirname );
use File::Slurp;
use File::Copy;
use Data::Dumper qw(Dumper);
use JSON::XS qw( decode_json );
use JSON;
use Encode qw/ decode /;

use File::Basename; 
use File::Spec::Functions qw/ canonpath /; 

my $dirname = basename(getcwd);

sub randomUUID()
{
$ug    = Data::UUID->new;
$uuid = $ug->create();
$rid   = $ug->to_string($uuid);
return $rid;
}

$rid  = randomUUID();
#check certslogs
#use Net::SSLeay;
#$Net::SSLeay::trace = 3;
$ENV{'PERL_LWP_SSL_VERIFY_HOSTNAME'} = 0;
$ENV{HTTPS_DEBUG} = 1;


binmode STDOUT, ":encoding(UTF-8)";


my $resultStr="";
#=pod
my $postdata='{
    "buildVersion": "1.1.1272",
    "environment": "prodgreen",
    "isDevelopementRun": true,
    "runName": "test add_run procedure",
    "runUid": "'.$rid.'",
    "testTeam": "LSBET",
    "testType": "BACKEND"
}
';

#=cut


  $URL="http://localhost/myreporter/api/reporter/$dirname/add";


$URL =~ tr/'/\"/;

sub randomclient(){

my @clientslist = @lines;

$clientver = $clientslist[ rand @clientslist ];
return $clientver;

}

sub  trim { my $s = shift; $s =~ s/^\s+|\s+$//g; return $s };


sub postWebPage{
($url,$useragent,$json) = ($_[0], $_[1], $_[2]);
my $ua = LWP::UserAgent->new;
$ua->agent($useragent);
$ua->timeout(100);
#$ua->show_progress(1);
$ua->env_proxy;
my @headers_list = (
'Content-Type' => 'application/json',
'Accept' => 'application/json',
'Accept-Encoding' => 'gzip, deflate',
#'Connection' => 'keep-alive',
'Content' => $json
);

#print Dumper(@headers_list);

my $response = $ua->post($url,@headers_list);
#print Dumper($response);


my $responsecode=$response->code;
if ( $response->is_success ) {
	$response = $response->decoded_content( charset => 'none' );
	
	#Encode::from_to($response, 'windows-1251', 'utf-8');
	#$response =~ s/\\u([[:xdigit:]]{1,4})/chr(eval("0x$1"))/egis;
	
	return ($responsecode,$response);
}
else {
	#die $response->status_line;
	return ($responsecode,$response->status_line.":".$response->decoded_content( charset => 'none' ));
}
}


my $useragent = randomclient();




my ($responsecode,$response) = postWebPage($URL,$useragent,$postdata);


if($responsecode==200)
{
print $response;
#my $dataArray = decode_json($response);
#print Dumper($dataArray);
}
else
{
print $response;
print $responsecode;
}
