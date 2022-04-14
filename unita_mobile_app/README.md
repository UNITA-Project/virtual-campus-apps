# unita_mobile_app

## Creation of the mobile application

This project was created with the help of this [tutorial](https://reactnative.dev/docs/environment-setup).

## Launch application with the phone emulator

``` bash
npx react-native start or yarn react-native start
```

That start Metro Bundler.

``` bash
npx react-native run-android or yarn react-native run-android
```

This command launch application in the Android emulator.

## My virtual device

Name: Pixel 2 API 30
Resolution: 1080x1920: 420dpi
API: 30
Target: Android 11.0

## Build debug apk (Android)

In `/unita_mobile_app/`

``` bash
yarn react-native bundle --platform android --dev false --entry-file index.js --bundle-output android/app/src/main/assets/index.android.bundle --assets-dest android/app/src/main/res
```

Then `cd /android/` and
``` bash
./gradlew assembleDebug
```

You'll find the apk here: `/unita_mobile_app/android/app/build/output/apk/debug/`
