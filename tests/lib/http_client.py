#!/usr/bin/env python3
"""
Cliente HTTP minimo para tests Praesidium.

Objetivo:
    Probar endpoints reales cuando se proporcionan variables de entorno.

Seguridad:
    Este helper no decide si un test es destructivo; los tests destructivos deben
    llamar tambien a destructive_guard.require_lab_confirmation().
"""
from __future__ import annotations
from urllib import request, parse
from http.cookiejar import CookieJar
from urllib.error import HTTPError
import re


class PraesidiumHttpClient:
    def __init__(self, base_url: str):
        self.base_url = base_url.rstrip('/')
        self.cookies = CookieJar()
        self.opener = request.build_opener(request.HTTPCookieProcessor(self.cookies))
        self.csrf_token: str | None = None

    def get(self, path: str):
        return self._open('GET', path)

    def post_form(self, path: str, data: dict[str, str], csrf: bool = False):
        body = parse.urlencode(data).encode()
        headers = {'Content-Type': 'application/x-www-form-urlencoded'}
        if csrf and self.csrf_token:
            headers['X-CSRF-Token'] = self.csrf_token
        return self._open('POST', path, body=body, headers=headers)

    def post_json(self, path: str, payload: str, csrf: bool = False):
        headers = {'Content-Type': 'application/json'}
        if csrf and self.csrf_token:
            headers['X-CSRF-Token'] = self.csrf_token
        return self._open('POST', path, body=payload.encode(), headers=headers)

    def _open(self, method: str, path: str, body: bytes | None = None, headers: dict | None = None):
        url = self.base_url + '/' + path.lstrip('/')
        req = request.Request(url, data=body, method=method, headers=headers or {})
        try:
            resp = self.opener.open(req, timeout=15)
            content = resp.read().decode('utf-8', errors='replace')
            return resp.status, dict(resp.headers), content
        except HTTPError as exc:
            content = exc.read().decode('utf-8', errors='replace')
            return exc.code, dict(exc.headers), content

    def login(self, username: str, password: str) -> bool:
        status, _, body = self.post_form('/login.php', {'username': username, 'password': password})
        if status not in (200, 302):
            return False
        # Captura token CSRF si la shell principal lo publica en meta tag.
        status, _, body = self.get('/mainpage.php')
        match = re.search(r'<meta[^>]+name=["\']csrf-token["\'][^>]+content=["\']([^"\']+)', body, re.I)
        if match:
            self.csrf_token = match.group(1)
        return status in (200, 302)
