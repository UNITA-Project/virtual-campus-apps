#!/usr/bin/perl

### Modify here as your wish ###
my $LOCAL_BRANCH  = 'master';
my $REMOTE_NAME   = 'brainless';
my $REMOTE_SERVER = 'git@save.my.brian';
my $REMOTE_BRANCH = 'master';

# This script will do....
# git remote add $REMOTE_NAME $REMOTE_SERVER;
# git push $REMOTE_NAME $LOCAL_BRANCH:$REMOTE_BRANCH

################################

use File::Basename;
my $filename = $0;
my $where = dirname($filename);
chdir $where;

&create_remote if not &remote_exist;
&create_branch if not &branch_exist;
&commit_changes;
&push_to_remote;

sub create_branch() {
    print "Create branch: git checkout -b $LOCAL_BRANCH\n";
    print `git checkout -b $LOCAL_BRANCH`;
}

sub create_remote() {
    print "Create remote: git remote add $REMOTE_NAME $REMOTE_SERVER\n";
    print `git remote add $REMOTE_NAME $REMOTE_SERVER`;
}

sub remote_exist() {
    my @remotes = `git remote`;
    foreach(@remotes) {
	return 1 if $_ =~ /.*$REMOTE_NAME.*/;
    }

    print "Remote $REMOTE_NAME not exist\n";
    return 0;
}

sub branch_exist() {
    my @branches = `git branch`;
    foreach(@branches) {
	return 1 if $_ =~ /.*$LOCAL_BRANCH.*/;
    }

    print "Branch $LOCAL_BRANCH not exist\n";
    return 0;
}

sub commit_changes {
    chomp (my $date = `date -R`);

    print `git checkout $LOCAL_BRANCH`;
    print `git add uploads/*`;
    print `git add wiki.d/*`;
    print `git add *`;
    print `git commit -m "$date"`;
}

sub push_to_remote {
    print `git push $REMOTE_NAME $LOCAL_BRANCH:$REMOTE_BRANCH`;
}
