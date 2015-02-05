# Keboola Docker Demo

This is a working example of a Docker working in KBC. Functionality is simple, splits long text columns into multiple rows and adds index number into a new column and writes the result into `/data/out/tables/sliced.csv` file.

## Install & build

```
git clone https://github.com/keboola/docker-demo.git
cd docker-demo
sudo docker build --no-cache -t keboola/docker-demo .
```

## Runing a container

```
sudo docker run \
--volume=/home/ec2-user/data:/data \
--memory=64m \
--cpu-shares=1024 \
--rm \
keboola/docker-demo:latest 
```

Note: `--volume` needs to be adjusted accordingly.

## Sample configuration
Mapped to `/data/config.yml` 

```
storage:
  input:
    tables:
      0:
        source: in.c-main.data
        destination: source.csv
  output:
    tables:
      0:
        source: sliced.csv
        destination: out.c-main.data
parameters:
  primary_key_column: id
  data_column: text
  string_length: 255
```
## Data sample

### Source
Mapped to `/data/in/tables/source.csv`

```
id,text,some_other_column
1,"Short text","Whatever"
2,"Long text Long text Long text","Something else"
```

### Destination
Created in `/data/out/tables/sliced.csv`


```
id,text,row_number
1,"Short text",1
2,"Long text Long ",1
2,"text Long Text",2

```
