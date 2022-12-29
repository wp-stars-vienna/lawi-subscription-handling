# Continuous Deployments für WordPress Themes und Plugins mit GitHub Actions

Ziel ist ein Setup das automatisch beim Push in den Master/Main Branch 
einen neuen Release (git tag) erstellt, diesem eine semantic relase Version Number zuweist 
und den Code anschließend auf einen Webserver deployed.


## Vorbereitung

1. Neues Projekt in Github anlegen
2. YML File im genannten Verzeichnis erstellen .github/workflows/main.yml (+ Ordnerstruktur erstellen)
3. YML File wie folgt befüllen.

```
name: Bump version
on:
  push:
    branches: [ 'master' ]
jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Bump version and push tag
        id: tag_version
        uses: mathieudutour/github-tag-action@v5
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
      - name: Create a GitHub release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: ${{ steps.tag_version.outputs.new_tag }}
          release_name: Release ${{ steps.tag_version.outputs.new_tag }}
          body: ${{ steps.tag_version.outputs.changelog }}
      - name: Replace Version in Plugin 1
        uses: datamonsters/replace-action@v2
        with:
          files:  'Readme.md'
          replacements:  "%%version%%=${{ steps.tag_version.outputs.new_version }}"
      - name: Replace Version in Plugin 2
        uses: datamonsters/replace-action@v2
        with:
          files:  'info.json'
          replacements:  "%%version%%=${{ steps.tag_version.outputs.new_version }}"  
      - name: Replace Version in Plugin 3
        uses: datamonsters/replace-action@v2
        with:
          files:  'wps-plugin/wps-plugin.php'
          replacements:  "%%version%%=${{ steps.tag_version.outputs.new_version }}"  
      - name: Sync to Production
        env:
          dest: 'e93477@e93477-ssh.services.easyname.eu:/data/web/e93477/html/apps/plugins.michael-ritsch.com'
        run: |
          echo "${{secrets.DEPLOY_KEY}}" > deploy_key
          chmod 600 ./deploy_key
          zip -r wps-plugin.zip wps-plugin
          rsync -zvr --delete \
            -e 'ssh -i ./deploy_key -o StrictHostKeyChecking=no -p 22' \
            --include 'wps-plugin.zip' \
            --include 'info.json' \
            --exclude '*' \
            ./ ${{env.dest}}
```

## RSYNC

Das Deployment der Daten wird über RSYNC funktionieren, dazu verwenden wir diesen Befehl hier:

```
rsync -chav --delete --exclude /.git/ --exclude /.github/ --exclude /.idea/ ./ e93477@e93477-ssh.services.easyname.eu:/data/web/e93477/html/apps/incredible-deployment
```

Diesen Befehl wollen wir von der Github CI (Github Actions) ausführen lassen so dass die Daten direkt von Github auf den Server geschickt werden.

## SSH Configuration am productive Server (in meinem Fall Easyname Webhosting *Server* :D )

#### Key anlegen mit der eigenen github Adresse:
```
ssh-keygen -t ed25519 -C "office@michael-ritsch.com"
```

#### SSH Agend Prozess am Server starten:
```
$ eval "$(ssh-agent -s)"
```

#### Prüfen ob das unten genannte File existiert sonst optional vorher anlegen:
```
[optional] touch ~/.ssh/config
nano ~/.ssh/config
```

In dem File muss nun folgendes stehen:
 ```
Host *
  AddKeysToAgent yes
  UseKeychain yes
  IdentityFile ~/.ssh/id_ed25519
```

#### Public Key Rechte anpassen und hinzufügen

```
chmod 600 ~/.ssh/id_ed25519
ssh-add ~/.ssh/id_ed25519
```

#### authorized_keys

Checken ob authorized_keys file schon existiert, falls nicht bitte erzeugen und folgende Rechte setzen.
Der .ssh Ordner sollte übrigen chmod 700 haben.

```
touch ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
```

Anschließend muss der Public Key noch in diesem File eingetragen werden:

```
cat ~/.ssh/id_ed25519.pub >> ~/.ssh/authorized_keys
```

## Private Key in Github hinterlegen

Direkt im Github Repo gibt es in den Einstellungen einen Bereich names "Secrets" - hier gehört ein neues
Secret mit dem Namen "DEPLOY_KEY" angelegt - dort muss der private key einmal hinein kopiert werden.
Den bekommt man wenn man folgenden Code am Production Server ausführt:

```
nano ~/.ssh/id_ed25519
```

## Links und weitere Infos

- Blogartikel: https://css-tricks.com/continuous-deployments-for-wordpress-using-github-actions/
- Github-Tag-Action: https://github.com/mathieudutour/github-tag-action
- Github Doku zum Thema Keys anlegen: https://docs.github.com/en/free-pro-team@latest/github/authenticating-to-github/generating-a-new-ssh-key-and-adding-it-to-the-ssh-agent
