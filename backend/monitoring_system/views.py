from django.shortcuts import render
from django.http import HttpResponse

# Create your views here.


def hello(request):
    return HttpResponse("Hello ATUL, You ARE ON SITE: domain/monitoring_system/hello")


def hellofile(request):
    return render(request, "hello.html")
