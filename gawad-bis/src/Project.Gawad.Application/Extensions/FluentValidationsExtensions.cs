using System.Reflection;
using FluentValidation;
using FluentValidation.AspNetCore;
using Microsoft.Extensions.DependencyInjection;

namespace Project.Gawad.Application.Extensions;

public static class FluentValidationsExtensions
{
    /// <summary>
    /// Register the Validators
    /// </summary>
    /// <param name="services"></param>
    /// <returns></returns>
    public static IServiceCollection AddFluentValidations(this IServiceCollection services)
    {
        services.AddFluentValidation(fv => { fv.DisableDataAnnotationsValidation = true; });

        services.AddValidatorsFromAssembly(Assembly.GetExecutingAssembly());
        
        return services;
    }
}