# git-train

## Usage

### Prepare the docker env
```bash
$ docker-compose build
$ d run -it \
    -v "%HOME%\.gitconfig:/root/.gitconfig:ro" \
    -v %CD%:/repo \
    -v "%HOME%\.ssh:/root/.ssh:ro" \
    git-train_dev bash
# if you want to push      
0root@d9b378c6aa3f:/code# eval `ssh-agent`
root@d9b378c6aa3f:/repo# cat ~/.ssh/id_rsa | ssh-add -k -
root@d9b378c6aa3f:/code# cd /repo/
```
### Actual script
```bash
php /code/index.php rebase [first branch of train] [rebase root]
```

So if you have branches:

* my-train-1-based-on-master
* train2-based-on-train-1
* train3-based-on-train-2

You should run:
```bash
php /code/index.php rebase my-train-1-based-on-master master
```
